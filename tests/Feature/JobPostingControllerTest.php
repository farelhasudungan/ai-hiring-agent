<?php

use App\Models\Application;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function companyUser(string $role = 'hr'): User
{
    return User::factory()->create(['role' => $role]);
}

function validJobPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Backend Engineer',
        'department' => 'Engineering',
        'location' => 'Jakarta',
        'status' => 'open',
        'description' => 'Build APIs.',
        'requirements' => 'Experience with Laravel.',
        'required_skills' => 'PHP, Laravel,  MySQL, PHP',
        'nice_to_have' => "Docker\nRedis",
        'min_experience_years' => 3,
    ], $overrides);
}

test('guests are redirected to login', function () {
    $this->get(route('job-postings.index'))->assertRedirectToRoute('login');
    $this->post(route('job-postings.store'), validJobPayload())->assertRedirectToRoute('login');
});

test('index lists only postings of the user company and filters by status', function () {
    $user = companyUser('viewer');
    JobPosting::factory()->for($user->company)->create(['title' => 'Mine Open', 'status' => 'open']);
    JobPosting::factory()->for($user->company)->create(['title' => 'Mine Draft', 'status' => 'draft']);
    JobPosting::factory()->create(['title' => 'Other Company']);

    $this->actingAs($user)->get(route('job-postings.index'))
        ->assertOk()->assertSee('Mine Open')->assertSee('Mine Draft')->assertDontSee('Other Company');

    $this->actingAs($user)->get(route('job-postings.index', ['status' => 'draft']))
        ->assertOk()->assertSee('Mine Draft')->assertDontSee('Mine Open');
});

test('users without a company cannot see job postings', function () {
    $user = User::factory()->withoutCompany()->create();

    $this->actingAs($user)->get(route('job-postings.index'))->assertForbidden();
});

test('hr can create a job posting scoped to their company with skills parsed', function () {
    $user = companyUser('hr');

    $this->actingAs($user)->post(route('job-postings.store'), validJobPayload(['company_id' => 999]))
        ->assertRedirect();

    $jobPosting = JobPosting::query()->sole();
    expect($jobPosting->company_id)->toBe($user->company_id)
        ->and($jobPosting->required_skills)->toBe(['PHP', 'Laravel', 'MySQL'])
        ->and($jobPosting->nice_to_have)->toBe(['Docker', 'Redis']);
});

test('viewers and interviewers cannot create, update, or delete', function (string $role) {
    $user = companyUser($role);
    $jobPosting = JobPosting::factory()->for($user->company)->create();

    $this->actingAs($user)->get(route('job-postings.create'))->assertForbidden();
    $this->actingAs($user)->post(route('job-postings.store'), validJobPayload())->assertForbidden();
    $this->actingAs($user)->put(route('job-postings.update', $jobPosting), validJobPayload())->assertForbidden();
    $this->actingAs($user)->delete(route('job-postings.destroy', $jobPosting))->assertForbidden();

    $this->assertDatabaseCount('job_postings', 1);
})->with(['viewer', 'interviewer']);

test('members can view a posting but not one from another company', function () {
    $user = companyUser('viewer');
    $own = JobPosting::factory()->for($user->company)->create(['title' => 'Own Role']);
    $other = JobPosting::factory()->create();

    $this->actingAs($user)->get(route('job-postings.show', $own))->assertOk()->assertSee('Own Role');
    $this->actingAs($user)->get(route('job-postings.show', $other))->assertForbidden();
});

test('creation validates input', function (array $overrides, string $field) {
    $this->actingAs(companyUser())->post(route('job-postings.store'), validJobPayload($overrides))
        ->assertSessionHasErrors($field);

    $this->assertDatabaseCount('job_postings', 0);
})->with([
    'missing title' => [['title' => ''], 'title'],
    'bad status' => [['status' => 'archived'], 'status'],
    'missing description' => [['description' => ''], 'description'],
    'no skills' => [['required_skills' => ' , '], 'required_skills'],
    'negative experience' => [['min_experience_years' => -1], 'min_experience_years'],
]);

test('hr can update a posting', function () {
    $user = companyUser('admin');
    $jobPosting = JobPosting::factory()->for($user->company)->create();

    $this->actingAs($user)->get(route('job-postings.edit', $jobPosting))->assertOk();
    $this->actingAs($user)->put(route('job-postings.update', $jobPosting), validJobPayload(['title' => 'Updated', 'status' => 'closed']))
        ->assertRedirectToRoute('job-postings.show', $jobPosting);

    $this->assertDatabaseHas('job_postings', ['id' => $jobPosting->id, 'title' => 'Updated', 'status' => 'closed']);
});

test('users cannot update or delete another company posting', function () {
    $user = companyUser('admin');
    $other = JobPosting::factory()->create();

    $this->actingAs($user)->put(route('job-postings.update', $other), validJobPayload())->assertForbidden();
    $this->actingAs($user)->delete(route('job-postings.destroy', $other))->assertForbidden();

    $this->assertDatabaseHas('job_postings', ['id' => $other->id, 'title' => $other->title]);
});

test('hr can delete a posting without applications', function () {
    $user = companyUser('hr');
    $jobPosting = JobPosting::factory()->for($user->company)->create();

    $this->actingAs($user)->delete(route('job-postings.destroy', $jobPosting))->assertRedirectToRoute('job-postings.index');

    $this->assertDatabaseMissing('job_postings', ['id' => $jobPosting->id]);
});

test('a posting with applications cannot be deleted', function () {
    $user = companyUser('hr');
    $jobPosting = JobPosting::factory()->for($user->company)->create();
    Application::factory()->create(['company_id' => $user->company_id, 'job_id' => $jobPosting->id]);

    $this->actingAs($user)->delete(route('job-postings.destroy', $jobPosting))
        ->assertRedirectToRoute('job-postings.show', $jobPosting);

    $this->assertDatabaseHas('job_postings', ['id' => $jobPosting->id]);
});
