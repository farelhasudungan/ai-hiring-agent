<?php

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Form;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('guests must log in before opening job postings', function () {
    $this->get(route('job-postings.index'))->assertRedirectToRoute('login');
    $this->post(route('job-postings.store'), [])->assertRedirectToRoute('login');
});

test('users without a company cannot open job postings', function () {
    $user = User::factory()->withoutCompany()->create();

    $this->actingAs($user)->get(route('job-postings.index'))->assertForbidden();
    $this->actingAs($user)->get(route('job-postings.create'))->assertForbidden();
});

test('the index only lists job postings from the current company', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $ownPosting = JobPosting::factory()->create(['company_id' => $user->company_id, 'title' => 'Own company role']);
    JobPosting::factory()->create(['title' => 'Other company role']);

    $this->actingAs($user)->get(route('job-postings.index'))
        ->assertOk()
        ->assertSee($ownPosting->title)
        ->assertDontSee('Other company role');
});

test('the index can be filtered by status', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $draft = JobPosting::factory()->create(['company_id' => $user->company_id, 'status' => 'draft', 'title' => 'Draft role']);
    $open = JobPosting::factory()->create(['company_id' => $user->company_id, 'status' => 'open', 'title' => 'Open role']);

    $this->actingAs($user)->get(route('job-postings.index', ['status' => 'draft']))
        ->assertOk()
        ->assertSee($draft->title)
        ->assertDontSee($open->title);

    $this->actingAs($user)->get(route('job-postings.index', ['status' => 'archived']))
        ->assertOk()
        ->assertSee($draft->title)
        ->assertSee($open->title);
});

test('viewers can browse job postings but cannot manage them', function () {
    $user = User::factory()->create(['role' => 'viewer']);
    $jobPosting = JobPosting::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)->get(route('job-postings.index'))->assertOk();
    $this->actingAs($user)->get(route('job-postings.show', $jobPosting))->assertOk();
    $this->actingAs($user)->get(route('job-postings.create'))->assertForbidden();

    $this->actingAs($user)->post(route('job-postings.store'), [
        'title' => 'Should not exist',
        'status' => 'draft',
        'description' => 'Nope.',
    ])->assertForbidden();

    $this->actingAs($user)->put(route('job-postings.update', $jobPosting), [
        'title' => 'Changed',
        'status' => 'open',
        'description' => 'Changed.',
    ])->assertForbidden();

    $this->actingAs($user)->delete(route('job-postings.destroy', $jobPosting))->assertForbidden();

    $this->assertDatabaseHas('job_postings', ['id' => $jobPosting->id, 'title' => $jobPosting->title]);
});

test('admins can open the create and edit forms with only their company forms', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create(['company_id' => $user->company_id, 'title' => 'Screening form']);
    $foreignForm = Form::factory()->create(['title' => 'Foreign form']);
    $jobPosting = JobPosting::factory()->create(['company_id' => $user->company_id, 'title' => 'Existing role']);

    $this->actingAs($user)->get(route('job-postings.create'))
        ->assertOk()
        ->assertSee('Role title')
        ->assertSee($form->title)
        ->assertDontSee($foreignForm->title);

    $this->actingAs($user)->get(route('job-postings.edit', $jobPosting))
        ->assertOk()
        ->assertSee('Existing role')
        ->assertSee($form->title)
        ->assertDontSee($foreignForm->title);
});

test('hr users can create a job posting with parsed skills', function () {
    $user = User::factory()->create(['role' => 'hr']);
    $form = Form::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)->post(route('job-postings.store'), [
        'title' => 'Senior Laravel Engineer',
        'department' => 'Engineering',
        'location' => 'Remote',
        'status' => 'open',
        'description' => 'Build thoughtful hiring tools.',
        'requirements' => 'Five years of PHP.',
        'required_skills' => 'PHP, Laravel,  PostgreSQL ,,',
        'nice_to_have' => 'Docker, AWS',
        'min_experience_years' => 5,
        'form_id' => $form->id,
        'company_id' => 999,
    ])->assertRedirect();

    $jobPosting = JobPosting::query()->sole();

    expect($jobPosting->company_id)->toBe($user->company_id)
        ->and($jobPosting->required_skills)->toBe(['PHP', 'Laravel', 'PostgreSQL'])
        ->and($jobPosting->nice_to_have)->toBe(['Docker', 'AWS'])
        ->and($jobPosting->form_id)->toBe($form->id);
});

test('creating a job posting validates its input', function (array $payload, string $field) {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)->post(route('job-postings.store'), $payload)
        ->assertSessionHasErrors($field);

    $this->assertDatabaseCount('job_postings', 0);
})->with([
    'missing title' => [['status' => 'draft', 'description' => 'Role.'], 'title'],
    'invalid status' => [['title' => 'Role', 'status' => 'archived', 'description' => 'Role.'], 'status'],
    'missing description' => [['title' => 'Role', 'status' => 'draft'], 'description'],
    'negative experience' => [['title' => 'Role', 'status' => 'draft', 'description' => 'Role.', 'min_experience_years' => -1], 'min_experience_years'],
]);

test('a job posting cannot attach a form from another company', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $otherForm = Form::factory()->create();

    $this->actingAs($user)->post(route('job-postings.store'), [
        'title' => 'Role',
        'status' => 'draft',
        'description' => 'Role.',
        'form_id' => $otherForm->id,
    ])->assertSessionHasErrors('form_id');

    $this->assertDatabaseCount('job_postings', 0);
});

test('the show page displays the job posting and its linked form', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create(['company_id' => $user->company_id, 'title' => 'Engineering application']);
    $jobPosting = JobPosting::factory()->create([
        'company_id' => $user->company_id,
        'form_id' => $form->id,
        'title' => 'Platform Engineer',
        'required_skills' => ['PHP', 'Laravel'],
    ]);

    $this->actingAs($user)->get(route('job-postings.show', $jobPosting))
        ->assertOk()
        ->assertSee('Platform Engineer')
        ->assertSee('Engineering application')
        ->assertSee('Laravel');
});

test('job postings from another company are not accessible', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $foreignPosting = JobPosting::factory()->create();

    $this->actingAs($user)->get(route('job-postings.show', $foreignPosting))->assertForbidden();
    $this->actingAs($user)->get(route('job-postings.edit', $foreignPosting))->assertForbidden();

    $this->actingAs($user)->put(route('job-postings.update', $foreignPosting), [
        'title' => 'Hijacked',
        'status' => 'open',
        'description' => 'Hijacked.',
    ])->assertForbidden();

    $this->actingAs($user)->delete(route('job-postings.destroy', $foreignPosting))->assertForbidden();
});

test('admins can update a job posting', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $jobPosting = JobPosting::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)->put(route('job-postings.update', $jobPosting), [
        'title' => 'Updated role',
        'department' => 'Product',
        'location' => 'Jakarta',
        'status' => 'closed',
        'description' => 'Updated description.',
        'requirements' => 'Updated requirements.',
        'required_skills' => 'PHP, Redis',
        'nice_to_have' => '',
        'min_experience_years' => 2,
    ])->assertRedirectToRoute('job-postings.show', $jobPosting);

    $jobPosting->refresh();

    expect($jobPosting->title)->toBe('Updated role')
        ->and($jobPosting->status)->toBe('closed')
        ->and($jobPosting->required_skills)->toBe(['PHP', 'Redis'])
        ->and($jobPosting->nice_to_have)->toBe([]);
});

test('the status endpoint updates only the status', function () {
    $user = User::factory()->create(['role' => 'hr']);
    $jobPosting = JobPosting::factory()->create(['company_id' => $user->company_id, 'status' => 'draft']);

    $this->actingAs($user)
        ->from(route('job-postings.show', $jobPosting))
        ->patch(route('job-postings.update-status', $jobPosting), ['status' => 'open'])
        ->assertRedirectToRoute('job-postings.show', $jobPosting);

    expect($jobPosting->fresh()->status)->toBe('open');
});

test('the status endpoint rejects unknown statuses', function () {
    $user = User::factory()->create(['role' => 'hr']);
    $jobPosting = JobPosting::factory()->create(['company_id' => $user->company_id, 'status' => 'draft']);

    $this->actingAs($user)
        ->from(route('job-postings.show', $jobPosting))
        ->patch(route('job-postings.update-status', $jobPosting), ['status' => 'archived'])
        ->assertSessionHasErrors('status');

    expect($jobPosting->fresh()->status)->toBe('draft');
});

test('admins can delete a job posting without applications', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $jobPosting = JobPosting::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)->delete(route('job-postings.destroy', $jobPosting))
        ->assertRedirectToRoute('job-postings.index');

    $this->assertDatabaseMissing('job_postings', ['id' => $jobPosting->id]);
});

test('job postings with applications cannot be deleted', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $jobPosting = JobPosting::factory()->create(['company_id' => $user->company_id]);

    Application::factory()->create([
        'company_id' => $user->company_id,
        'job_id' => $jobPosting->id,
        'candidate_id' => Candidate::factory()->create(['company_id' => $user->company_id])->id,
    ]);

    $this->actingAs($user)
        ->from(route('job-postings.show', $jobPosting))
        ->delete(route('job-postings.destroy', $jobPosting))
        ->assertSessionHasErrors('job_posting');

    $this->assertDatabaseHas('job_postings', ['id' => $jobPosting->id]);
});
