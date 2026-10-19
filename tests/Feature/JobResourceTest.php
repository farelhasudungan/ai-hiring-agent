<?php

use App\Filament\Resources\Jobs\JobResource;
use App\Filament\Resources\Jobs\Pages\CreateJob;
use App\Filament\Resources\Jobs\Pages\EditJob;
use App\Filament\Resources\Jobs\Pages\ListJobs;
use App\Filament\Resources\Jobs\Pages\ViewJob;
use App\Filament\Resources\Jobs\RelationManagers\ApplicationsRelationManager;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.env' => 'local']);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('the jobs table only lists and searches the signed in company jobs', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $own = Job::factory()->create(['company_id' => $user->company_id, 'title' => 'Internal developer']);
    $other = Job::factory()->create(['title' => 'Other developer']);

    Livewire::actingAs($user)->test(ListJobs::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$other])
        ->searchTable('Other developer')
        ->assertCanNotSeeTableRecords([$own, $other]);
});

test('an admin creates a job in their company without a company input', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $other = Company::factory()->create();

    Livewire::actingAs($user)->test(CreateJob::class)
        ->assertFormFieldDoesNotExist('company_id')
        ->fillForm([
            'title' => 'Laravel Developer',
            'department' => 'Engineering',
            'location' => 'Surabaya',
            'status' => 'open',
            'description' => 'Build our hiring product.',
            'requirements' => 'Comfortable with PHP.',
            'required_skills' => ['PHP', 'Laravel'],
            'nice_to_have' => ['PostgreSQL'],
            'min_experience_years' => 2,
        ])
        ->set('data.company_id', $other->id)
        ->call('create')
        ->assertHasNoFormErrors();

    $job = Job::query()->sole();
    expect($job->company_id)->toBe($user->company_id);
    expect($job->required_skills)->toBe(['PHP', 'Laravel']);
    expect($job->nice_to_have)->toBe(['PostgreSQL']);
    expect($job->min_experience_years)->toBe(2);
    $this->get(route('jobs.apply', $job))->assertOk()->assertSee('Laravel Developer');
});

test('invalid job fields cannot be saved', function (array $overrides, array $errors) {
    $user = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($user)->test(CreateJob::class)
        ->fillForm([
            'title' => 'Developer',
            'description' => 'Work with our team.',
            'status' => 'draft',
            'required_skills' => ['PHP'],
            ...$overrides,
        ])
        ->call('create')
        ->assertHasFormErrors($errors);

    $this->assertDatabaseCount('jobs', 0);
})->with([
    'required fields' => [['title' => '', 'description' => '', 'status' => null, 'required_skills' => []], ['title' => 'required', 'description' => 'required', 'status' => 'required', 'required_skills' => 'required']],
    'unknown status' => [['status' => 'published'], ['status' => 'in']],
    'negative experience' => [['min_experience_years' => -1], ['min_experience_years' => 'min']],
    'fractional experience' => [['min_experience_years' => 1.5], ['min_experience_years' => 'integer']],
    'oversized title' => [['title' => str_repeat('a', 256)], ['title' => 'max']],
    'oversized skill' => [['required_skills' => [str_repeat('a', 101)]], ['required_skills.0' => 'max']],
]);

test('editing a job cannot move it to another company', function () {
    $user = User::factory()->create(['role' => 'hr']);
    $job = Job::factory()->create(['company_id' => $user->company_id]);
    $other = Company::factory()->create();

    Livewire::actingAs($user)->test(EditJob::class, ['record' => $job->getRouteKey()])
        ->assertFormFieldDoesNotExist('company_id')
        ->fillForm(['title' => 'Updated title', 'status' => 'open'])
        ->set('data.company_id', $other->id)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($job->fresh()->company_id)->toBe($user->company_id);
    expect($job->fresh()->title)->toBe('Updated title');
});

test('another company job cannot be opened by guessing the resource record id', function (string $page) {
    $user = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create();

    $this->actingAs($user)->get(JobResource::getUrl($page, ['record' => $job]))->assertNotFound();
})->with(['view', 'edit']);

test('a read only company member cannot create or edit jobs', function () {
    $user = User::factory()->create(['role' => 'viewer']);
    $job = Job::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)->get(JobResource::getUrl('create'))->assertForbidden();
    $this->get(JobResource::getUrl('edit', ['record' => $job]))->assertForbidden();
});

test('guests cannot access job management', function () {
    $this->get(JobResource::getUrl())->assertRedirectToRoute('filament.admin.auth.login');
});

test('public application links are shown only for open jobs on the view page', function (string $status, bool $visible) {
    $user = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['company_id' => $user->company_id, 'status' => $status]);
    $page = Livewire::actingAs($user)->test(ViewJob::class, ['record' => $job->getRouteKey()]);

    if ($visible) {
        $page->assertSee('href="'.route('jobs.apply', $job).'"', false);
    } else {
        $page->assertDontSee('href="'.route('jobs.apply', $job).'"', false);
    }
})->with(['draft' => ['draft', false], 'open' => ['open', true], 'closed' => ['closed', false]]);

test('each jobs table record has only a view action linking to its detail page', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['company_id' => $user->company_id]);

    $page = Livewire::actingAs($user)->test(ListJobs::class)
        ->assertTableActionVisible('view', $job)
        ->assertTableActionHasUrl('view', JobResource::getUrl('view', ['record' => $job]), $job)
        ->assertTableActionDoesNotExist('edit')
        ->assertTableActionDoesNotExist('publicApplication');

    expect($page->instance()->getTable()->getRecordActions())->toHaveCount(1);
});

test('the job view page displays details and edit and delete buttons for managers', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create([
        'company_id' => $user->company_id,
        'description' => 'Build our recruiting platform.',
        'requirements' => 'Experience with web applications.',
        'required_skills' => ['PHP', 'Laravel'],
        'nice_to_have' => ['PostgreSQL'],
    ]);

    $this->actingAs($user)->get(JobResource::getUrl('view', ['record' => $job]))->assertOk();
    Livewire::test(ViewJob::class, ['record' => $job->getRouteKey()])
        ->assertSee($job->title)
        ->assertSee($job->description)
        ->assertSee($job->requirements)
        ->assertSee('PHP')
        ->assertSee('PostgreSQL')
        ->assertActionVisible('edit')
        ->assertActionHasUrl('edit', JobResource::getUrl('edit', ['record' => $job]))
        ->assertActionVisible('delete');
});

test('job detail headings and breadcrumbs use the job title without a view label', function (string $title) {
    $user = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['company_id' => $user->company_id, 'title' => $title]);

    $response = $this->actingAs($user)->get(JobResource::getUrl('view', ['record' => $job]));
    $response->assertOk();

    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $heading = $xpath->query('//h1')->item(0);
    $labels = $xpath->query('//nav[contains(@class, "fi-breadcrumbs")]//li/*[contains(@class, "fi-breadcrumbs-item-label")]');

    expect(trim($heading->textContent))->toBe($title);
    expect($labels->length)->toBe(2);
    expect(trim($labels->item(0)->textContent))->toBe('Lowongan');
    expect($labels->item(0)->getAttribute('href'))->toBe(JobResource::getUrl());
    expect(trim($labels->item(1)->textContent))->toBe($title);
    expect($labels->item(1)->getAttribute('aria-current'))->toBe('page');
})->with(['Android Developer', 'Backend Engineer']);

test('a read only member may view a company job without edit or delete actions', function () {
    $user = User::factory()->create(['role' => 'viewer']);
    $job = Job::factory()->create(['company_id' => $user->company_id]);

    Livewire::actingAs($user)->test(ViewJob::class, ['record' => $job->getRouteKey()])
        ->assertOk()
        ->assertSee($job->description)
        ->assertActionHidden('edit')
        ->assertActionHidden('delete')
        ->call('mountAction', 'delete')
        ->call('callMountedAction');

    $this->assertModelExists($job);
});

test('a manager can delete an unused job from its view page', function () {
    $user = User::factory()->create(['role' => 'hr']);
    $job = Job::factory()->create(['company_id' => $user->company_id]);

    Livewire::actingAs($user)->test(ViewJob::class, ['record' => $job->getRouteKey()])
        ->callAction('delete')
        ->assertRedirect(JobResource::getUrl());

    $this->assertModelMissing($job);
});

test('jobs with applications cannot be deleted from their view page', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['company_id' => $user->company_id]);
    Application::factory()->create(['company_id' => $user->company_id, 'job_id' => $job->id]);

    Livewire::actingAs($user)->test(ViewJob::class, ['record' => $job->getRouteKey()])
        ->assertActionVisible('edit')
        ->assertActionHidden('delete')
        ->call('mountAction', 'delete')
        ->call('callMountedAction');

    $this->assertModelExists($job);
});

test('an unused job can be deleted through its edit page', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['company_id' => $user->company_id]);

    Livewire::actingAs($user)->test(EditJob::class, ['record' => $job->getRouteKey()])
        ->callAction('delete');

    $this->assertModelMissing($job);
});

test('a job with applications cannot be deleted', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['company_id' => $user->company_id]);
    Application::factory()->create(['company_id' => $user->company_id, 'job_id' => $job->id]);

    Livewire::actingAs($user)->test(EditJob::class, ['record' => $job->getRouteKey()])
        ->assertActionHidden('delete')
        ->call('mountAction', 'delete')
        ->call('callMountedAction');

    $this->assertModelExists($job);
});

test('the applications table appears below job details only on the view page', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['company_id' => $user->company_id]);

    Livewire::actingAs($user)->test(ViewJob::class, ['record' => $job->getRouteKey()])
        ->assertSeeLivewire(ApplicationsRelationManager::class);

    Livewire::test(EditJob::class, ['record' => $job->getRouteKey()])
        ->assertDontSeeLivewire(ApplicationsRelationManager::class);
});

test('the applications table only lists the current job applications and company candidates', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['company_id' => $user->company_id]);
    $own = Application::factory()->create(['company_id' => $user->company_id, 'job_id' => $job->id]);
    $anotherJob = Application::factory()->create(['company_id' => $user->company_id]);
    $anotherCompany = Application::factory()->create();
    $wrongCompany = Application::factory()->create(['job_id' => $job->id]);
    $wrongCandidate = Application::factory()->create([
        'company_id' => $user->company_id,
        'job_id' => $job->id,
        'candidate_id' => $anotherCompany->candidate_id,
    ]);

    Livewire::actingAs($user)->test(ApplicationsRelationManager::class, [
        'ownerRecord' => $job,
        'pageClass' => ViewJob::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$anotherJob, $anotherCompany, $wrongCompany, $wrongCandidate])
        ->searchTable($anotherCompany->candidate->email)
        ->assertCountTableRecords(0);
});

test('applications can be searched by candidate name or email', function (string $search) {
    $user = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['company_id' => $user->company_id]);
    $candidate = Candidate::factory()->create([
        'company_id' => $user->company_id,
        'full_name' => 'Nadia Pratama',
        'email' => 'nadia@example.com',
        'phone' => '081234567890',
    ]);
    $match = Application::factory()->create([
        'company_id' => $user->company_id, 'job_id' => $job->id, 'candidate_id' => $candidate->id,
    ]);
    $other = Application::factory()->create(['company_id' => $user->company_id, 'job_id' => $job->id]);

    Livewire::actingAs($user)->test(ApplicationsRelationManager::class, [
        'ownerRecord' => $job,
        'pageClass' => ViewJob::class,
    ])
        ->searchTable($search)
        ->assertCanSeeTableRecords([$match])
        ->assertCanNotSeeTableRecords([$other])
        ->assertSee('Nadia Pratama')
        ->assertSee('nadia@example.com')
        ->assertSee('081234567890');
})->with(['Nadia Pratama', 'nadia@example.com']);

test('applications are ordered newest first and can be filtered by status', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['company_id' => $user->company_id]);
    $older = Application::factory()->create([
        'company_id' => $user->company_id, 'job_id' => $job->id,
        'submitted_at' => '2026-10-19 08:00:00', 'status' => 'received',
    ]);
    $newer = Application::factory()->create([
        'company_id' => $user->company_id, 'job_id' => $job->id,
        'submitted_at' => '2026-10-19 09:00:00', 'status' => 'shortlisted',
    ]);

    Livewire::actingAs($user)->test(ApplicationsRelationManager::class, [
        'ownerRecord' => $job,
        'pageClass' => ViewJob::class,
    ])
        ->assertCanSeeTableRecords([$newer, $older], inOrder: true)
        ->assertSee('19 Oct 2026 09:00')
        ->filterTable('status', 'shortlisted')
        ->assertCanSeeTableRecords([$newer])
        ->assertCanNotSeeTableRecords([$older])
        ->assertSee('Lolos seleksi');
});

test('company members can read applications without write actions', function (string $role) {
    $user = User::factory()->create(['role' => $role]);
    $job = Job::factory()->create(['company_id' => $user->company_id]);
    $application = Application::factory()->create(['company_id' => $user->company_id, 'job_id' => $job->id]);

    Livewire::actingAs($user)->test(ApplicationsRelationManager::class, [
        'ownerRecord' => $job,
        'pageClass' => ViewJob::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$application])
        ->assertTableActionDoesNotExist('create')
        ->assertTableActionDoesNotExist('associate')
        ->assertTableActionDoesNotExist('edit')
        ->assertTableActionDoesNotExist('delete')
        ->assertTableBulkActionDoesNotExist('delete');
})->with(['admin', 'hr', 'interviewer', 'viewer']);

test('the applications component refuses a job belonging to another company', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create();

    Livewire::actingAs($user)->test(ApplicationsRelationManager::class, [
        'ownerRecord' => $job,
        'pageClass' => ViewJob::class,
    ])->assertForbidden();
});

test('a user without job viewing permission cannot access the applications component', function () {
    $user = User::factory()->create(['role' => 'unknown']);
    $job = Job::factory()->create(['company_id' => $user->company_id]);

    Livewire::actingAs($user)->test(ApplicationsRelationManager::class, [
        'ownerRecord' => $job,
        'pageClass' => ViewJob::class,
    ])->assertForbidden();
});

test('guests cannot access the applications component', function () {
    $job = Job::factory()->create();

    Livewire::test(ApplicationsRelationManager::class, [
        'ownerRecord' => $job,
        'pageClass' => ViewJob::class,
    ])->assertForbidden();
});

test('the applications table shows an empty state when the job has no applications', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['company_id' => $user->company_id]);

    Livewire::actingAs($user)->test(ApplicationsRelationManager::class, [
        'ownerRecord' => $job,
        'pageClass' => ViewJob::class,
    ])
        ->assertCountTableRecords(0)
        ->assertSee('Belum ada lamaran');
});

test('candidate supplied text is escaped in the applications table', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $job = Job::factory()->create(['company_id' => $user->company_id]);
    $candidate = Candidate::factory()->create([
        'company_id' => $user->company_id,
        'full_name' => '<script>alert("name")</script>',
        'email' => '<script>alert("email")</script>',
        'phone' => '<script>alert(1)</script>',
    ]);
    Application::factory()->create([
        'company_id' => $user->company_id, 'job_id' => $job->id, 'candidate_id' => $candidate->id,
    ]);

    Livewire::actingAs($user)->test(ApplicationsRelationManager::class, [
        'ownerRecord' => $job,
        'pageClass' => ViewJob::class,
    ])
        ->assertSee($candidate->full_name)
        ->assertSee($candidate->email)
        ->assertSee($candidate->phone)
        ->assertDontSee($candidate->full_name, false)
        ->assertDontSee($candidate->email, false)
        ->assertDontSee($candidate->phone, false);
});
