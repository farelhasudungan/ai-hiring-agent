<?php

use App\Actions\ReviewApplication;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Applications\Pages\EditApplication;
use App\Filament\Resources\Applications\Pages\ListApplications;
use App\Filament\Resources\Applications\Pages\ViewApplication;
use App\Filament\Resources\Jobs\Pages\ViewJob;
use App\Filament\Resources\Jobs\RelationManagers\ApplicationsRelationManager;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.env' => 'local']);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('applications are scoped to the company including candidate and job ownership', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $own = Application::factory()->create(['company_id' => $user->company_id]);
    $other = Application::factory()->create();
    $wrongCandidate = Application::factory()->create(['company_id' => $user->company_id, 'candidate_id' => $other->candidate_id]);
    $wrongJob = Application::factory()->create(['company_id' => $user->company_id, 'job_id' => $other->job_id]);

    Livewire::actingAs($user)->test(ListApplications::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$other, $wrongCandidate, $wrongJob])
        ->searchTable($other->candidate->full_name)
        ->assertCountTableRecords(0);
});

test('applications can be searched by candidate or job title', function (string $search) {
    $user = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['company_id' => $user->company_id, 'title' => 'Android Developer']);
    $candidate = Candidate::factory()->create(['company_id' => $user->company_id, 'full_name' => 'Nadia Pratama']);
    $match = Application::factory()->create([
        'company_id' => $user->company_id, 'job_id' => $job->id, 'candidate_id' => $candidate->id,
    ]);
    $other = Application::factory()->create(['company_id' => $user->company_id]);

    Livewire::actingAs($user)->test(ListApplications::class)
        ->searchTable($search)
        ->assertCanSeeTableRecords([$match])
        ->assertCanNotSeeTableRecords([$other]);
})->with(['Nadia Pratama', 'Android Developer']);

test('application filters combine job status and source without offering another company jobs', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['company_id' => $user->company_id]);
    $otherJob = Job::factory()->create();
    $match = Application::factory()->create([
        'company_id' => $user->company_id, 'job_id' => $job->id, 'status' => 'shortlisted', 'source' => 'form',
    ]);
    $wrongStatus = Application::factory()->create([
        'company_id' => $user->company_id, 'job_id' => $job->id, 'status' => 'received', 'source' => 'form',
    ]);
    $wrongSource = Application::factory()->create([
        'company_id' => $user->company_id, 'job_id' => $job->id, 'status' => 'shortlisted', 'source' => 'manual',
    ]);
    $anotherJob = Application::factory()->create([
        'company_id' => $user->company_id, 'status' => 'shortlisted', 'source' => 'form',
    ]);

    $page = Livewire::actingAs($user)->test(ListApplications::class)
        ->filterTable('job_id', $job->id)
        ->filterTable('status', 'shortlisted')
        ->filterTable('source', 'form')
        ->assertCanSeeTableRecords([$match])
        ->assertCanNotSeeTableRecords([$wrongStatus, $wrongSource, $anotherJob]);

    expect($page->instance()->getTable()->getFilter('job_id')->getOptions())
        ->toHaveKey($job->id)->not->toHaveKey($otherJob->id);

    $page->filterTable('job_id', $otherJob->id)->assertCountTableRecords(0);
});

test('the score column displays pending and existing scores without generating a score', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $pending = Application::factory()->create(['company_id' => $user->company_id]);
    $scored = Application::factory()->create(['company_id' => $user->company_id, 'latest_match_score' => 82]);

    Livewire::actingAs($user)->test(ListApplications::class)
        ->assertTableColumnStateSet('latest_match_score', null, $pending)
        ->assertTableColumnStateSet('latest_match_score', 82, $scored)
        ->assertSee('Belum dinilai');

    expect($pending->fresh()->latest_match_score)->toBeNull();
});

test('application details display candidate documents notes and management actions', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $candidate = Candidate::factory()->create([
        'company_id' => $user->company_id, 'phone' => '081234567890',
        'linkedin_url' => 'https://linkedin.com/in/nadia', 'portfolio_url' => 'https://example.com/portfolio',
    ]);
    $application = Application::factory()->create([
        'company_id' => $user->company_id, 'candidate_id' => $candidate->id, 'notes' => 'Follow up next week.',
    ]);
    $pdf = ApplicationDocument::factory()->create(['application_id' => $application->id]);
    $docx = ApplicationDocument::factory()->create([
        'application_id' => $application->id, 'file_name' => 'resume.docx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ]);
    $download = route('filament.admin.application-documents.show', ['application' => $application->id, 'document' => $pdf->id]);

    $this->actingAs($user)->get(ApplicationResource::getUrl('view', ['record' => $application]))->assertOk();
    Livewire::test(ViewApplication::class, ['record' => $application->id])
        ->assertSee($candidate->full_name)->assertSee($candidate->email)->assertSee('081234567890')
        ->assertSee($candidate->linkedin_url)->assertSee($candidate->portfolio_url)
        ->assertSee($application->job->title)->assertSee('Follow up next week.')
        ->assertSee('resume.pdf')->assertSee('resume.docx')
        ->assertSee('href="'.$download.'"', false)
        ->assertSee('href="'.$download.'?preview=1"', false)
        ->assertDontSee('href="'.route('filament.admin.application-documents.show', [
            'application' => $application->id, 'document' => $docx->id, 'preview' => 1,
        ]).'"', false)
        ->assertActionVisible('edit')->assertActionVisible('archive')->assertActionDoesNotExist('delete');
});

test('managers may edit only status and notes and review metadata is assigned on status changes', function () {
    $this->travelTo(now()->startOfSecond());
    $user = User::factory()->create(['role' => 'hr']);
    $application = Application::factory()->create(['company_id' => $user->company_id, 'source' => 'form']);
    $foreign = Application::factory()->create();
    $original = $application->only(['company_id', 'candidate_id', 'job_id', 'source', 'submitted_at']);

    Livewire::actingAs($user)->test(EditApplication::class, ['record' => $application->id])
        ->assertFormFieldDoesNotExist('candidate_id')->assertFormFieldDoesNotExist('job_id')
        ->assertFormFieldDoesNotExist('source')->assertFormFieldDoesNotExist('latest_match_score')
        ->assertFormFieldDoesNotExist('reviewed_by')->assertFormFieldDoesNotExist('reviewed_at')
        ->fillForm(['status' => 'interview', 'notes' => 'Invite for a technical interview.'])
        ->set('data.company_id', $foreign->company_id)
        ->set('data.candidate_id', $foreign->candidate_id)
        ->set('data.job_id', $foreign->job_id)
        ->set('data.source', 'manual')
        ->set('data.latest_match_score', 100)
        ->set('data.reviewed_by', 999)
        ->set('data.reviewed_at', '2000-01-01 00:00:00')
        ->call('save')->assertHasNoFormErrors()
        ->assertRedirect(ApplicationResource::getUrl('view', ['record' => $application]));

    $application->refresh();
    expect($application->only(array_keys($original)))->toEqual($original);
    expect($application->status)->toBe('interview');
    expect($application->notes)->toBe('Invite for a technical interview.');
    expect($application->latest_match_score)->toBeNull();
    expect($application->reviewed_by)->toBe($user->id);
    expect($application->reviewed_at->equalTo(now()))->toBeTrue();
});

test('editing notes without changing status preserves review metadata', function (bool $alreadyReviewed) {
    $user = User::factory()->create(['role' => 'admin']);
    $reviewer = User::factory()->create(['company_id' => $user->company_id]);
    $application = Application::factory()->create([
        'company_id' => $user->company_id,
        'reviewed_by' => $alreadyReviewed ? $reviewer->id : null,
        'reviewed_at' => $alreadyReviewed ? '2026-10-18 10:00:00' : null,
    ]);
    $original = $application->only(['reviewed_by', 'reviewed_at']);

    Livewire::actingAs($user)->test(EditApplication::class, ['record' => $application->id])
        ->fillForm(['status' => 'received', 'notes' => 'Updated notes only.'])
        ->call('save')->assertHasNoFormErrors();

    expect($application->fresh()->only(['reviewed_by', 'reviewed_at']))->toEqual($original);
    expect($application->fresh()->notes)->toBe('Updated notes only.');
})->with([false, true]);

test('invalid review values cannot be saved', function (array $data, array $errors) {
    $user = User::factory()->create(['role' => 'admin']);
    $application = Application::factory()->create(['company_id' => $user->company_id]);

    Livewire::actingAs($user)->test(EditApplication::class, ['record' => $application->id])
        ->fillForm($data)->call('save')->assertHasFormErrors($errors);

    expect($application->fresh()->status)->toBe('received');
    expect($application->fresh()->notes)->toBeNull();
    expect($application->fresh()->reviewed_by)->toBeNull();
})->with([
    'required status' => [['status' => null], ['status' => 'required']],
    'unknown status' => [['status' => 'published'], ['status' => 'in']],
    'oversized notes' => [['notes' => str_repeat('a', 10001)], ['notes' => 'max']],
]);

test('archiving retains application documents and notes and stamps the reviewer', function (string $pageClass) {
    $this->travelTo(now()->startOfSecond());
    Storage::fake('local');
    $user = User::factory()->create(['role' => 'admin']);
    $application = Application::factory()->create(['company_id' => $user->company_id, 'notes' => 'Keep this history.']);
    $document = ApplicationDocument::factory()->create(['application_id' => $application->id]);
    Storage::disk('local')->put($document->file_path, 'saved CV');

    Livewire::actingAs($user)->test($pageClass, ['record' => $application->id])
        ->callAction('archive')->assertActionHidden('archive');

    $application->refresh();
    expect($application->status)->toBe('archived');
    expect($application->notes)->toBe('Keep this history.');
    expect($application->reviewed_by)->toBe($user->id);
    expect($application->reviewed_at->equalTo(now()))->toBeTrue();
    $this->assertModelExists($document);
    Storage::disk('local')->assertExists($document->file_path);
})->with([ViewApplication::class, EditApplication::class]);

test('the list offers archive but never create or delete actions', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $application = Application::factory()->create(['company_id' => $user->company_id]);

    Livewire::actingAs($user)->test(ListApplications::class)
        ->assertActionDoesNotExist('create')
        ->assertTableActionDoesNotExist('delete')->assertTableBulkActionDoesNotExist('delete')
        ->assertTableActionHasUrl('view', ApplicationResource::getUrl('view', ['record' => $application]), $application)
        ->callTableAction('archive', $application);

    expect($application->fresh()->status)->toBe('archived');
    expect(Gate::forUser($user)->allows('create', Application::class))->toBeFalse();
    expect(Gate::forUser($user)->allows('delete', $application))->toBeFalse();
});

test('read only members can view applications but cannot edit or invoke archive', function (string $role) {
    $user = User::factory()->create(['role' => $role]);
    $application = Application::factory()->create(['company_id' => $user->company_id]);

    Livewire::actingAs($user)->test(ListApplications::class)
        ->assertCanSeeTableRecords([$application])->assertTableActionHidden('archive', $application)
        ->call('mountAction', 'archive', [], ['table' => true, 'recordKey' => (string) $application->id])
        ->call('callMountedAction');
    Livewire::test(ViewApplication::class, ['record' => $application->id])
        ->assertActionHidden('edit')->assertActionHidden('archive')
        ->call('mountAction', 'archive')->call('callMountedAction');
    $this->get(ApplicationResource::getUrl('edit', ['record' => $application]))->assertForbidden();

    expect($application->fresh()->status)->toBe('received');
    expect($application->fresh()->reviewed_by)->toBeNull();
})->with(['interviewer', 'viewer']);

test('foreign application pages are not found', function (string $page) {
    $user = User::factory()->create(['role' => 'admin']);
    $application = Application::factory()->create();

    $this->actingAs($user)->get(ApplicationResource::getUrl($page, ['record' => $application]))->assertNotFound();
})->with(['view', 'edit']);

test('a forged foreign archive row cannot be acted upon', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $foreign = Application::factory()->create();

    Livewire::actingAs($user)->test(ListApplications::class)
        ->call('mountAction', 'archive', [], ['table' => true, 'recordKey' => (string) $foreign->id])
        ->call('callMountedAction');

    expect($foreign->fresh()->status)->toBe('received');
});

test('guests and unknown roles cannot access applications', function () {
    $this->get(ApplicationResource::getUrl())->assertRedirectToRoute('filament.admin.auth.login');
    $user = User::factory()->create(['role' => 'unknown']);

    $this->actingAs($user)->get(ApplicationResource::getUrl())->assertForbidden();
});

test('application views escape candidate names and notes', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $candidate = Candidate::factory()->create(['company_id' => $user->company_id, 'full_name' => '<script>alert("name")</script>']);
    $application = Application::factory()->create([
        'company_id' => $user->company_id, 'candidate_id' => $candidate->id, 'notes' => '<script>alert("notes")</script>',
    ]);

    Livewire::actingAs($user)->test(ViewApplication::class, ['record' => $application->id])
        ->assertSee($candidate->full_name)->assertDontSee($candidate->full_name, false)
        ->assertSee($application->notes)->assertDontSee($application->notes, false);
});

test('job application rows link to the application detail page', function () {
    $user = User::factory()->create(['role' => 'viewer']);
    $application = Application::factory()->create(['company_id' => $user->company_id]);

    Livewire::actingAs($user)->test(ApplicationsRelationManager::class, [
        'ownerRecord' => $application->job, 'pageClass' => ViewJob::class,
    ])->assertTableActionHasUrl('view', ApplicationResource::getUrl('view', ['record' => $application]), $application);
});

test('application policies require the correct company and role for management', function (string $role, bool $view, bool $manage) {
    $user = User::factory()->create(['role' => $role]);
    $own = Application::factory()->create(['company_id' => $user->company_id]);
    $other = Application::factory()->create();
    $gate = Gate::forUser($user);

    expect($gate->allows('viewAny', Application::class))->toBe($view);
    expect($gate->allows('view', $own))->toBe($view);
    expect($gate->allows('update', $own))->toBe($manage);
    expect($gate->allows('view', $other))->toBeFalse();
    expect($gate->allows('update', $other))->toBeFalse();
    expect($gate->allows('create', Application::class))->toBeFalse();
    expect($gate->allows('delete', $own))->toBeFalse();
    expect($gate->allows('deleteAny', Application::class))->toBeFalse();
})->with([
    'admin' => ['admin', true, true],
    'hr' => ['hr', true, true],
    'interviewer' => ['interviewer', true, false],
    'viewer' => ['viewer', true, false],
    'unknown' => ['unknown', false, false],
]);

test('the review operation ignores protected attributes and only replaces metadata when status actually changes', function () {
    $this->travelTo(now()->startOfSecond());
    $user = User::factory()->create(['role' => 'admin']);
    $previousReviewer = User::factory()->create(['company_id' => $user->company_id]);
    $application = Application::factory()->create([
        'company_id' => $user->company_id, 'latest_match_score' => 80,
        'reviewed_by' => $previousReviewer->id, 'reviewed_at' => '2026-10-18 10:00:00',
    ]);
    $review = new ReviewApplication;

    $review->handle($application, $user, [
        'status' => 'archived', 'company_id' => 999, 'latest_match_score' => 100,
        'reviewed_by' => $previousReviewer->id, 'reviewed_at' => '2000-01-01 00:00:00',
    ]);

    expect($application->company_id)->toBe($user->company_id);
    expect($application->latest_match_score)->toBe(80);
    expect($application->reviewed_by)->toBe($user->id);
    expect($application->reviewed_at->equalTo(now()))->toBeTrue();
    $reviewedAt = $application->reviewed_at->copy();

    $this->travel(1)->hours();
    $review->handle($application, $user, ['status' => 'archived']);

    expect($application->reviewed_at->equalTo($reviewedAt))->toBeTrue();
});
