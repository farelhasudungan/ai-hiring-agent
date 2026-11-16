<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChatConversationRequest;
use App\Http\Requests\StoreChatMessageRequest;
use App\Models\Application;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Chat\CandidateChatService;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ChatConversation::class);

        $company = $request->user()->company;

        return view('chat.index', [
            'conversations' => $this->recentConversations($request),
            'jobPostings' => $company->jobPostings()->orderBy('title')->get(['id', 'title']),
            'selectedJobId' => $request->integer('job') ?: null,
            'hasAiProvider' => $company->aiProviderSetting !== null,
        ]);
    }

    public function store(StoreChatConversationRequest $request): RedirectResponse
    {
        $user = $request->user();
        $jobId = $request->validated('job_id');

        $conversation = ChatConversation::query()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'job_id' => $jobId,
            'title' => 'New search',
        ]);

        return redirect()->route('chat.show', $conversation)
            ->with('chat_first_message', $request->validated('message'));
    }

    public function show(Request $request, ChatConversation $conversation): View
    {
        Gate::authorize('view', $conversation);

        $conversation->load('job:id,title');

        $messages = $conversation->messages()->orderBy('id')->get();
        $candidateIds = $messages->pluck('candidate_ids')->flatten()->filter()->unique()->all();
        $applications = Application::query()
            ->where('company_id', $conversation->company_id)
            ->whereKey($candidateIds)
            ->with(['job:id,title', 'aiScreenings' => fn ($screenings) => $screenings->where('status', 'success')->latest('id')])
            ->get()
            ->keyBy('id');

        $service = app(CandidateChatService::class);

        return view('chat.show', [
            'conversation' => $conversation,
            'conversations' => $this->recentConversations($request),
            'firstMessage' => session('chat_first_message'),
            'messages' => $messages->map(fn (ChatMessage $message): array => [
                'role' => $message->role,
                'content' => $message->content,
                'candidates' => collect($message->candidate_ids ?? [])
                    ->map(fn (int $id) => $applications->get($id))
                    ->filter()
                    ->map(fn (Application $application): array => $service->card($application))
                    ->values()
                    ->all(),
            ])->all(),
            'hasAiProvider' => $request->user()->company->aiProviderSetting !== null,
            'maxLength' => StoreChatMessageRequest::MAX_LENGTH,
        ]);
    }

    public function destroy(ChatConversation $conversation): RedirectResponse
    {
        Gate::authorize('delete', $conversation);

        $conversation->delete();

        return redirect()->route('chat.index')->with('status', 'Search deleted.');
    }

    /**
     * Stores the question and streams the answer as server-sent events:
     * `candidates`, many `chunk`, then `done` (or `error`).
     */
    public function storeMessage(StoreChatMessageRequest $request, ChatConversation $conversation, CandidateChatService $chat): StreamedResponse|JsonResponse
    {
        if ($conversation->company->aiProviderSetting === null) {
            return response()->json(['message' => 'Set up an AI provider in AI settings before using the chat.'], 422);
        }

        $question = $conversation->messages()->create([
            'role' => ChatMessage::ROLE_USER,
            'content' => $request->validated('message'),
        ]);

        if ($conversation->title === 'New search') {
            $conversation->update(['title' => Str::limit($question->content, 60)]);
        }

        return response()->stream(function () use ($chat, $conversation, $question): void {
            // An agent loop makes several provider calls, so allow more than one request's time.
            set_time_limit(180);

            try {
                foreach ($chat->respond($conversation, $question) as $event) {
                    $this->emit($event['event'], $event['data']);
                }
            } catch (DomainException|AiProviderException $exception) {
                $this->emit('error', ['message' => $exception->getMessage()]);
            } catch (Throwable $exception) {
                report($exception);
                $this->emit('error', ['message' => 'Something went wrong while answering. Try again.']);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * @return Collection<int, ChatConversation>
     */
    private function recentConversations(Request $request): Collection
    {
        return ChatConversation::query()
            ->where('user_id', $request->user()->id)
            ->latest('updated_at')
            ->limit(40)
            ->get(['id', 'title', 'updated_at']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function emit(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: '.json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
