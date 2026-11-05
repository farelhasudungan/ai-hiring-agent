<?php

use App\Models\Application;
use App\Models\Candidate;
use App\Models\JobPosting;
use App\Models\User;
use App\Services\JobPostingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('the service creates job postings scoped to the user company', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $jobPosting = app(JobPostingService::class)->create($user, [
        'title' => 'Backend Engineer',
        'department' => null,
        'location' => 'Remote',
        'status' => 'draft',
        'description' => 'Build the API.',
        'requirements' => null,
        'required_skills' => 'PHP, Laravel',
        'nice_to_have' => 'Redis',
        'min_experience_years' => 3,
        'form_id' => null,
    ]);

    expect($jobPosting->company_id)->toBe($user->company_id)
        ->and($jobPosting->required_skills)->toBe(['PHP', 'Laravel'])
        ->and($jobPosting->nice_to_have)->toBe(['Redis']);
});

test('the service defaults missing skills to an empty array', function () {
    $user = User::factory()->create(['role' => 'hr']);

    $jobPosting = app(JobPostingService::class)->create($user, [
        'title' => 'Designer',
        'status' => 'draft',
        'description' => 'Design the product.',
    ]);

    expect($jobPosting->required_skills)->toBe([])
        ->and($jobPosting->nice_to_have)->toBe([]);
});

test('the service refuses to create job postings for users without a company', function () {
    $user = User::factory()->withoutCompany()->create();

    expect(fn () => app(JobPostingService::class)->create($user, [
        'title' => 'Role',
        'status' => 'draft',
        'description' => 'Role.',
    ]))->toThrow(DomainException::class, 'You must belong to a company before creating a job posting.');
});

test('the service refuses to update a job posting from another company', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $foreignPosting = JobPosting::factory()->create();

    expect(fn () => app(JobPostingService::class)->update($user, $foreignPosting, [
        'title' => 'Hijacked',
    ]))->toThrow(DomainException::class, 'This job posting does not belong to your company.');
});

test('the service normalizes skills when updating', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $jobPosting = JobPosting::factory()->create(['company_id' => $user->company_id]);

    $updated = app(JobPostingService::class)->update($user, $jobPosting, [
        'required_skills' => ' PHP , Laravel ,PHP, ',
        'nice_to_have' => null,
    ]);

    expect($updated->required_skills)->toBe(['PHP', 'Laravel'])
        ->and($updated->nice_to_have)->toBe([]);
});

test('the service rejects unknown status values', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $jobPosting = JobPosting::factory()->create(['company_id' => $user->company_id]);

    expect(fn () => app(JobPostingService::class)->updateStatus($user, $jobPosting, 'archived'))
        ->toThrow(DomainException::class, 'The selected status is invalid.');
});

test('the service refuses to delete a job posting that has applications', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $jobPosting = JobPosting::factory()->create(['company_id' => $user->company_id]);

    Application::factory()->create([
        'company_id' => $user->company_id,
        'job_id' => $jobPosting->id,
        'candidate_id' => Candidate::factory()->create(['company_id' => $user->company_id])->id,
    ]);

    expect(fn () => app(JobPostingService::class)->delete($user, $jobPosting))
        ->toThrow(DomainException::class, 'This job posting has applications and cannot be deleted.');

    $this->assertDatabaseHas('job_postings', ['id' => $jobPosting->id]);
});

test('the service deletes a job posting without applications', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $jobPosting = JobPosting::factory()->create(['company_id' => $user->company_id]);

    app(JobPostingService::class)->delete($user, $jobPosting);

    $this->assertDatabaseMissing('job_postings', ['id' => $jobPosting->id]);
});
