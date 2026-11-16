<?php

use App\Models\AiProviderSetting;
use App\Models\AiScreening;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\User;
use App\Services\Chat\CandidateChatService;
use App\Services\Chat\CandidateRetriever;
use App\Services\Chat\CandidateSearchFilterParser;
use App\Services\Chat\Data\CandidateSearchFilters;
use App\Services\Chat\Tools\ChatToolContext;
use App\Services\Chat\Tools\CountCandidatesTool;
use App\Services\Chat\Tools\GetCandidateDetailTool;
use App\Services\Chat\Tools\SearchCandidatesTool;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
});

function chatUser(bool $withProvider = true, string $role = 'hr'): User
{
    $company = Company::factory()->create();

    if ($withProvider) {
        AiProviderSetting::factory()->for($company)->forProvider('openai', 'gpt-4o-mini')->create();
    }

    return User::factory()->create(['company_id' => $company->id, 'role' => $role]);
}

function candidateFor(Company $company, string $name, string $resume, ?int $score = null, ?JobPosting $job = null, ?int $experience = null): Application
{
    $job ??= JobPosting::factory()->for($company, 'company')->create(['title' => 'Backend Engineer']);

    $application = Application::factory()->create([
        'company_id' => $company->id,
        'job_id' => $job->id,
        'full_name' => $name,
        'latest_match_score' => $score,
    ]);

    ApplicationDocument::factory()->for($application)->create(['parsing_status' => 'success', 'parsed_text' => $resume]);

    if ($score !== null) {
        AiScreening::factory()->create([
            'application_id' => $application->id,
            'job_id' => $job->id,
            'status' => 'success',
            'match_score' => $score,
            'recommendation' => 'shortlist',
            'experience_years' => $experience,
            'matched_skills' => ['Laravel'],
        ]);
    }

    return $application;
}

/**
 * Fakes the provider: every non-stream request pops the next step (a tool call or a text
 * answer, the last step repeats), every stream request returns the final answer.
 *
 * @param  list<array{tool?: string, args?: array<string, mixed>, text?: string}>  $steps
 */
function fakeAgent(array $steps, string $answer = 'Jane fits.'): void
{
    $round = 0;
    $sse = collect(str_split($answer, 5))
        ->map(fn (string $piece): string => 'data: '.json_encode(['choices' => [['delta' => ['content' => $piece]]]])."\n\n")
        ->push("data: [DONE]\n\n")
        ->implode('');

    Http::fake(function (Request $request) use (&$round, $steps, $sse) {
        if (str_contains($request->body(), '"stream":true')) {
            return Http::response($sse, 200, ['Content-Type' => 'text/event-stream']);
        }

        $step = $steps[min($round, count($steps) - 1)];
        $round++;

        $message = isset($step['tool'])
            ? ['content' => null, 'tool_calls' => [['id' => 'call_'.$round, 'type' => 'function', 'function' => ['name' => $step['tool'], 'arguments' => json_encode($step['args'] ?? [])]]]]
            : ['content' => $step['text']];

        return Http::response(['choices' => [['message' => $message]]]);
    });
}

/**
 * @return list<Request>
 */
function providerRequests(bool $stream): array
{
    return collect(Http::recorded())
        ->map(fn (array $pair): Request => $pair[0])
        ->filter(fn (Request $request): bool => str_contains($request->body(), '"stream":true') === $stream)
        ->values()
        ->all();
}

function chatContext(Company $company, ?int $jobId = null): ChatToolContext
{
    return new ChatToolContext($company, $jobId);
}

test('hr can start a conversation scoped to a job of their company', function () {
    $user = chatUser();
    $job = JobPosting::factory()->for($user->company, 'company')->create();

    $this->actingAs($user)->post(route('chat.store'), ['job_id' => $job->id])
        ->assertRedirect(route('chat.show', ChatConversation::first()));

    expect(ChatConversation::first())->user_id->toBe($user->id)->job_id->toBe($job->id);
});

test('a conversation cannot be scoped to another company job', function () {
    $user = chatUser();
    $foreignJob = JobPosting::factory()->create();

    $this->actingAs($user)->post(route('chat.store'), ['job_id' => $foreignJob->id])
        ->assertSessionHasErrors('job_id');
});

test('viewers cannot use the chat and guests are redirected', function () {
    $this->get(route('chat.index'))->assertRedirect(route('login'));
    $this->actingAs(chatUser(role: 'viewer'))->get(route('chat.index'))->assertForbidden();
});

test('conversations are private to the user who started them', function () {
    $owner = chatUser();
    $conversation = ChatConversation::factory()->create(['company_id' => $owner->company_id, 'user_id' => $owner->id]);
    $colleague = User::factory()->create(['company_id' => $owner->company_id, 'role' => 'hr']);
    $outsider = chatUser();

    $this->actingAs($owner)->get(route('chat.show', $conversation))->assertOk();
    $this->actingAs($colleague)->get(route('chat.show', $conversation))->assertForbidden();
    $this->actingAs($outsider)->post(route('chat.messages.store', $conversation), ['message' => 'hi'])->assertForbidden();
});

test('the agent searches with a tool, then streams the answer and stores it with its candidates', function () {
    $user = chatUser();
    $jane = candidateFor($user->company, 'Jane Laravel', 'Senior Laravel developer with Docker.', score: 88, experience: 5);
    candidateFor($user->company, 'Joe Java', 'Java developer.', score: 40);
    $conversation = ChatConversation::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id, 'title' => 'New search']);

    fakeAgent([
        ['tool' => 'search_candidates', 'args' => ['skills' => ['Laravel'], 'min_experience_years' => 3]],
        ['text' => 'done searching'],
    ]);

    $stream = $this->actingAs($user)->post(route('chat.messages.store', $conversation), ['message' => 'Laravel devs with 3 years'])
        ->assertOk()
        ->assertHeader('Content-Type', 'text/event-stream; charset=utf-8')
        ->streamedContent();

    expect($stream)
        ->toContain('event: tool')
        ->toContain('Searching candidates')
        ->toContain('event: candidates')
        ->toContain('Jane Laravel')
        ->not->toContain('Joe Java')
        ->toContain('event: chunk')
        ->toContain('event: done');

    $assistant = $conversation->messages()->where('role', 'assistant')->sole();

    expect($assistant->content)->toBe('Jane fits.')
        ->and($assistant->candidate_ids)->toBe([$jane->id])
        ->and($assistant->tool_calls[0]['name'])->toBe('search_candidates')
        ->and($assistant->tool_calls[0]['arguments']['skills'])->toBe(['Laravel'])
        ->and($conversation->fresh()->title)->toBe('Laravel devs with 3 years');

    expect(providerRequests(stream: true))->toHaveCount(1)
        ->and(providerRequests(stream: true)[0]->body())->toContain('Jane Laravel')->not->toContain('Joe Java');
});

test('a direct reply without tools is replayed without asking the provider again', function () {
    $user = chatUser();
    $conversation = ChatConversation::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);

    fakeAgent([['text' => 'Hello, ask me about candidates.']]);

    $stream = $this->actingAs($user)->post(route('chat.messages.store', $conversation), ['message' => 'hi'])->streamedContent();

    expect($stream)->toContain('event: chunk')->toContain('event: done')->not->toContain('event: tool')
        ->and(providerRequests(stream: false))->toHaveCount(1)
        ->and(providerRequests(stream: true))->toHaveCount(0)
        ->and($conversation->messages()->where('role', 'assistant')->sole()->content)->toBe('Hello, ask me about candidates.');
});

test('the agent can chain tools and an unknown tool is reported back to it', function () {
    $user = chatUser();
    $jane = candidateFor($user->company, 'Jane Laravel', 'Laravel', score: 88);
    $conversation = ChatConversation::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);

    fakeAgent([
        ['tool' => 'delete_everything'],
        ['tool' => 'get_candidate_detail', 'args' => ['application_id' => $jane->id]],
        ['text' => 'ok'],
    ]);

    $stream = $this->actingAs($user)->post(route('chat.messages.store', $conversation), ['message' => 'tell me about Jane'])->streamedContent();

    expect($stream)->toContain('Jane Laravel')
        ->and(providerRequests(stream: false))->toHaveCount(3)
        ->and(providerRequests(stream: false)[1]->body())->toContain('Unknown tool delete_everything')
        ->and(providerRequests(stream: false)[2]->body())->toContain('"role":"tool"');
});

test('the agent loop stops after the maximum number of rounds', function () {
    $user = chatUser();
    $conversation = ChatConversation::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);

    fakeAgent([['tool' => 'count_candidates']]);

    $stream = $this->actingAs($user)->post(route('chat.messages.store', $conversation), ['message' => 'count forever'])->streamedContent();

    expect(providerRequests(stream: false))->toHaveCount(CandidateChatService::MAX_STEPS)
        ->and($stream)->toContain('event: done');
});

test('a provider failure is reported as an error event and no answer is stored', function () {
    $user = chatUser();
    $conversation = ChatConversation::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);

    Http::fake(['*' => Http::response('', 401)]);

    $stream = $this->actingAs($user)->post(route('chat.messages.store', $conversation), ['message' => 'anyone?'])->streamedContent();

    expect($stream)->toContain('event: error')->toContain('rejected the API key')
        ->and($conversation->messages()->where('role', 'assistant')->exists())->toBeFalse();
});

test('the chat is refused without an ai provider', function () {
    $user = chatUser(withProvider: false);
    $conversation = ChatConversation::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);

    $this->actingAs($user)->postJson(route('chat.messages.store', $conversation), ['message' => 'anyone?'])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Set up an AI provider in AI settings before using the chat.');

    expect($conversation->messages()->count())->toBe(0);
});

test('messages are validated', function () {
    $user = chatUser();
    $conversation = ChatConversation::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);

    $this->actingAs($user)->postJson(route('chat.messages.store', $conversation), ['message' => ''])->assertJsonValidationErrors('message');
    $this->actingAs($user)->postJson(route('chat.messages.store', $conversation), ['message' => str_repeat('a', 1001)])->assertJsonValidationErrors('message');
});

test('the retriever never returns candidates of another company', function () {
    $company = Company::factory()->create();
    $other = Company::factory()->create();
    candidateFor($company, 'Mine', 'Laravel developer', score: 70);
    candidateFor($other, 'Theirs', 'Laravel developer', score: 99);

    $names = app(CandidateRetriever::class)->search($company, new CandidateSearchFilters(terms: ['Laravel']))->pluck('full_name');

    expect($names->all())->toBe(['Mine']);
});

test('the retriever applies score, experience, job and term filters and ranks by term hits', function () {
    $company = Company::factory()->create();
    $otherJob = JobPosting::factory()->for($company, 'company')->create();
    $job = JobPosting::factory()->for($company, 'company')->create();
    $both = candidateFor($company, 'Both', 'Laravel and Docker expert', score: 60, job: $job, experience: 6);
    $one = candidateFor($company, 'One', 'Laravel only', score: 90, job: $job, experience: 6);
    candidateFor($company, 'Junior', 'Laravel and Docker', score: 95, job: $job, experience: 1);
    candidateFor($company, 'Elsewhere', 'Laravel and Docker', score: 95, job: $otherJob, experience: 8);
    $retriever = app(CandidateRetriever::class);

    $ranked = $retriever->search($company, new CandidateSearchFilters(terms: ['Laravel', 'Docker'], minExperienceYears: 3, minScore: 50), jobId: $both->job_id);

    expect($ranked->pluck('id')->all())->toBe([$both->id, $one->id]);
});

test('the retriever without criteria returns the best scored candidates first', function () {
    $company = Company::factory()->create();
    $low = candidateFor($company, 'Low', 'text', score: 30);
    $high = candidateFor($company, 'High', 'text', score: 80);
    $unscreened = candidateFor($company, 'Unscreened', 'text');

    $ids = app(CandidateRetriever::class)->search($company, new CandidateSearchFilters)->pluck('id')->all();

    expect($ids)->toBe([$high->id, $low->id, $unscreened->id]);
});

test('the filter parser keeps only valid fields', function () {
    $filters = app(CandidateSearchFilterParser::class)->parse(
        "Sure!\n```json\n{\"skills\":[\"PHP\",\"php\",5,\"\\\"Go\\\"\"],\"keywords\":[\"".str_repeat('x', 80)."\"],\"min_experience_years\":\"4\",\"min_score\":150,\"recommendations\":[\"shortlist\",\"hire\"],\"job_id\":99,\"gender\":\"female\"}\n```",
        [1, 2],
    );

    expect($filters->terms)->toBe(['PHP', 'Go'])
        ->and($filters->minExperienceYears)->toBe(4)
        ->and($filters->minScore)->toBeNull()
        ->and($filters->recommendations)->toBe(['shortlist'])
        ->and($filters->jobId)->toBeNull();
});

test('the filter parser returns empty filters for text that is not json', function () {
    expect(app(CandidateSearchFilterParser::class)->parse('no idea', [1])->hasCriteria())->toBeFalse();
});

test('the history page renders stored messages with their candidates', function () {
    $user = chatUser();
    $jane = candidateFor($user->company, 'Jane Laravel', 'Laravel', score: 88);
    $conversation = ChatConversation::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);
    $conversation->messages()->create(['role' => 'assistant', 'content' => 'Jane fits.', 'candidate_ids' => [$jane->id]]);

    $this->actingAs($user)->get(route('chat.show', $conversation))
        ->assertOk()
        ->assertSee('Jane fits.', false)
        ->assertSee('"name":"Jane Laravel"', false)
        ->assertSee(str_replace('/', '\\/', route('job-postings.candidates.show', [$jane->job_id, $jane])), false);
});

test('starting a search with a first message hands it to the conversation page', function () {
    $user = chatUser();

    $this->actingAs($user)->post(route('chat.store'), ['message' => 'Laravel devs'])
        ->assertRedirect(route('chat.show', ChatConversation::first()))
        ->assertSessionHas('chat_first_message', 'Laravel devs');

    $this->followingRedirects()->actingAs($user)->post(route('chat.store'), ['message' => 'Docker experts'])
        ->assertOk()
        ->assertSee('data-chat-first-message="Docker experts"', false);
});

test('the index lists previous searches of the user only', function () {
    $user = chatUser();
    ChatConversation::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id, 'title' => 'My Laravel search']);
    ChatConversation::factory()->create(['company_id' => $user->company_id, 'title' => 'Someone elses search']);

    $this->actingAs($user)->get(route('chat.index'))
        ->assertOk()
        ->assertSee('My Laravel search')
        ->assertDontSee('Someone elses search');
});

test('search_candidates stays inside the company and the locked job', function () {
    $company = Company::factory()->create();
    $lockedJob = JobPosting::factory()->for($company, 'company')->create();
    $otherJob = JobPosting::factory()->for($company, 'company')->create();
    $inJob = candidateFor($company, 'In Job', 'Laravel', score: 70, job: $lockedJob);
    candidateFor($company, 'Other Job', 'Laravel', score: 90, job: $otherJob);
    candidateFor(Company::factory()->create(), 'Foreign', 'Laravel', score: 99);

    $result = app(SearchCandidatesTool::class)->execute(chatContext($company, $lockedJob->id), ['skills' => ['Laravel'], 'job_id' => $otherJob->id, 'limit' => 50]);

    expect($result->applicationIds)->toBe([$inJob->id])
        ->and($result->data['candidates'])->toHaveCount(1);

    $open = app(SearchCandidatesTool::class)->execute(chatContext($company), ['skills' => ['Laravel']]);

    expect($open->data['candidates'])->toHaveCount(2);
});

test('get_candidate_detail returns the profile without contact details', function () {
    $company = Company::factory()->create();
    $application = candidateFor($company, 'Jane Laravel', 'Resume text about Laravel.', score: 88, experience: 5);
    AiScreening::query()->where('application_id', $application->id)->update(['parsed_response' => ['strengths' => ['Strong PHP'], 'gaps' => ['No Go']]]);

    $result = app(GetCandidateDetailTool::class)->execute(chatContext($company), ['application_id' => (string) $application->id]);

    expect($result->applicationIds)->toBe([$application->id])
        ->and($result->data)->toMatchArray(['name' => 'Jane Laravel', 'ai_score' => 88, 'strengths' => ['Strong PHP'], 'gaps' => ['No Go'], 'resume_text' => 'Resume text about Laravel.'])
        ->and($result->data)->not->toHaveKeys(['email', 'phone']);
});

test('get_candidate_detail refuses foreign candidates, candidates outside the locked job and bad ids', function () {
    $company = Company::factory()->create();
    $lockedJob = JobPosting::factory()->for($company, 'company')->create();
    $outsideJob = candidateFor($company, 'Outside', 'text', score: 50);
    $foreign = candidateFor(Company::factory()->create(), 'Foreign', 'text', score: 50);
    $tool = app(GetCandidateDetailTool::class);

    expect($tool->execute(chatContext($company), ['application_id' => $foreign->id])->data)->toHaveKey('error')
        ->and($tool->execute(chatContext($company, $lockedJob->id), ['application_id' => $outsideJob->id])->data)->toHaveKey('error')
        ->and($tool->execute(chatContext($company), ['application_id' => 'abc'])->data)->toHaveKey('error')
        ->and($tool->execute(chatContext($company), [])->applicationIds)->toBe([])
        ->and($tool->execute(chatContext($company), ['application_id' => $foreign->id])->applicationIds)->toBe([]);
});

test('count_candidates totals and groups matches', function () {
    $company = Company::factory()->create();
    $jobA = JobPosting::factory()->for($company, 'company')->create(['title' => 'Alpha']);
    $jobB = JobPosting::factory()->for($company, 'company')->create(['title' => 'Beta']);
    candidateFor($company, 'One', 'Docker and Laravel', score: 80, job: $jobA);
    candidateFor($company, 'Two', 'Docker', score: 60, job: $jobB);
    candidateFor($company, 'Three', 'Docker', job: $jobB);
    candidateFor($company, 'Four', 'Cobol', score: 90, job: $jobB);
    candidateFor(Company::factory()->create(), 'Foreign', 'Docker', score: 99);
    $tool = app(CountCandidatesTool::class);

    expect($tool->execute(chatContext($company), ['skills' => ['Docker']])->data['total'])->toBe(3)
        ->and($tool->execute(chatContext($company), ['skills' => ['Docker'], 'group_by' => 'job'])->data['groups'])->toBe(['Alpha' => 1, 'Beta' => 2])
        ->and($tool->execute(chatContext($company), ['skills' => ['Docker'], 'group_by' => 'recommendation'])->data['groups'])->toBe(['shortlist' => 2, 'not_screened' => 1])
        ->and($tool->execute(chatContext($company), ['group_by' => 'status'])->data['groups'])->toBe(['received' => 4])
        ->and($tool->execute(chatContext($company), ['skills' => ['Docker'], 'min_score' => 70])->data['total'])->toBe(1)
        ->and($tool->execute(chatContext($company), ['group_by' => 'salary'])->data)->toHaveKey('error');
});

test('a user can delete their own conversation together with its messages', function () {
    $user = chatUser();
    $conversation = ChatConversation::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);
    $conversation->messages()->create(['role' => 'user', 'content' => 'hello']);

    $this->actingAs($user)->delete(route('chat.destroy', $conversation))
        ->assertRedirect(route('chat.index'))
        ->assertSessionHas('status', 'Search deleted.');

    expect(ChatConversation::count())->toBe(0)->and(ChatMessage::count())->toBe(0);
});

test('other users cannot delete a conversation and guests are redirected', function () {
    $owner = chatUser();
    $conversation = ChatConversation::factory()->create(['company_id' => $owner->company_id, 'user_id' => $owner->id]);
    $colleague = User::factory()->create(['company_id' => $owner->company_id, 'role' => 'hr']);

    $this->actingAs($colleague)->delete(route('chat.destroy', $conversation))->assertForbidden();
    $this->actingAs(chatUser())->delete(route('chat.destroy', $conversation))->assertForbidden();
    auth()->logout();
    $this->delete(route('chat.destroy', $conversation))->assertRedirect(route('login'));

    expect(ChatConversation::count())->toBe(1);
});

test('the conversation page offers delete buttons with their confirmation dialog', function () {
    $user = chatUser();
    $conversation = ChatConversation::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);

    $this->actingAs($user)->get(route('chat.show', $conversation))
        ->assertOk()
        ->assertSee('data-chat-delete="'.route('chat.destroy', $conversation).'"', false)
        ->assertSee('id="chat-delete-dialog"', false);
});
