<?php

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Form;
use App\Models\FormAnswer;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/**
 * @return array{0: User, 1: JobPosting, 2: Form}
 */
function responseContext(string $role = 'admin'): array
{
    $user = User::factory()->create(['role' => $role]);
    $form = Form::factory()->create(['company_id' => $user->company_id]);

    $jobPosting = JobPosting::factory()->create([
        'company_id' => $user->company_id,
        'form_id' => $form->id,
    ]);

    return [$user, $jobPosting, $form];
}

test('guests must log in before opening responses', function () {
    $jobPosting = JobPosting::factory()->create();

    $this->get(route('job-postings.responses.index', $jobPosting))
        ->assertRedirectToRoute('login');
});

test('the response index only lists responses for the selected job posting', function () {
    [$user, $jobPosting] = responseContext();

    $ownCandidate = Candidate::factory()->create([
        'company_id' => $user->company_id,
        'full_name' => 'Own Candidate',
    ]);
    $otherCandidate = Candidate::factory()->create([
        'company_id' => $user->company_id,
        'full_name' => 'Other Candidate',
    ]);

    FormSubmission::factory()->create([
        'form_id' => $jobPosting->form_id,
        'job_posting_id' => $jobPosting->id,
        'candidate_id' => $ownCandidate->id,
    ]);

    FormSubmission::factory()->create([
        'form_id' => $jobPosting->form_id,
        'candidate_id' => $otherCandidate->id,
    ]);

    $this->actingAs($user)->get(route('job-postings.responses.index', $jobPosting))
        ->assertOk()
        ->assertSee('Own Candidate')
        ->assertDontSee('Other Candidate');
});

test('the response index can be filtered by status', function () {
    [$user, $jobPosting] = responseContext();

    $submitted = Candidate::factory()->create([
        'company_id' => $user->company_id,
        'full_name' => 'Submitted Candidate',
    ]);
    $processed = Candidate::factory()->create([
        'company_id' => $user->company_id,
        'full_name' => 'Processed Candidate',
    ]);

    FormSubmission::factory()->create([
        'form_id' => $jobPosting->form_id,
        'job_posting_id' => $jobPosting->id,
        'candidate_id' => $submitted->id,
        'status' => 'submitted',
    ]);
    FormSubmission::factory()->create([
        'form_id' => $jobPosting->form_id,
        'job_posting_id' => $jobPosting->id,
        'candidate_id' => $processed->id,
        'status' => 'processed',
    ]);

    $this->actingAs($user)->get(route('job-postings.responses.index', [
        'jobPosting' => $jobPosting,
        'status' => 'processed',
    ]))
        ->assertOk()
        ->assertSee('Processed Candidate')
        ->assertDontSee('Submitted Candidate');
});

test('the response detail page shows candidate answers', function () {
    [$user, $jobPosting, $form] = responseContext();

    $candidate = Candidate::factory()->create([
        'company_id' => $user->company_id,
        'full_name' => 'Ada Lovelace',
    ]);
    $application = Application::factory()->create([
        'company_id' => $user->company_id,
        'candidate_id' => $candidate->id,
        'job_id' => $jobPosting->id,
    ]);
    $submission = FormSubmission::factory()->create([
        'form_id' => $form->id,
        'job_posting_id' => $jobPosting->id,
        'candidate_id' => $candidate->id,
        'application_id' => $application->id,
    ]);
    $field = FormField::factory()->create([
        'form_id' => $form->id,
        'label' => 'Why do you want this role?',
    ]);
    FormAnswer::factory()->create([
        'form_submission_id' => $submission->id,
        'form_field_id' => $field->id,
        'value' => 'Five years of PHP experience.',
    ]);

    $this->actingAs($user)->get(route('job-postings.responses.show', [$jobPosting, $submission]))
        ->assertOk()
        ->assertSee('Ada Lovelace')
        ->assertSee('Why do you want this role?')
        ->assertSee('Five years of PHP experience.');
});

test('responses without a linked candidate show the name from their answers', function () {
    [$user, $jobPosting, $form] = responseContext();

    $submission = FormSubmission::factory()->create([
        'form_id' => $form->id,
        'job_posting_id' => $jobPosting->id,
        'candidate_id' => null,
    ]);

    $field = FormField::factory()->create([
        'form_id' => $form->id,
        'field_key' => 'full_name',
        'label' => 'Full name',
        'maps_to' => 'candidate.full_name',
    ]);

    FormAnswer::factory()->create([
        'form_submission_id' => $submission->id,
        'form_field_id' => $field->id,
        'value' => 'Ada Lovelace',
    ]);

    $this->actingAs($user)->get(route('job-postings.responses.index', $jobPosting))
        ->assertOk()
        ->assertSee('Ada Lovelace')
        ->assertSee('No email provided')
        ->assertDontSee('Unknown candidate');

    $this->actingAs($user)->get(route('job-postings.responses.show', [$jobPosting, $submission]))
        ->assertOk()
        ->assertSee('Ada Lovelace')
        ->assertSee('not linked to a candidate')
        ->assertSee('Add an email question mapped to');
});

test('viewers can read responses but cannot change or delete them', function () {
    [$user, $jobPosting, $form] = responseContext('viewer');
    $submission = FormSubmission::factory()->create([
        'form_id' => $form->id,
        'job_posting_id' => $jobPosting->id,
    ]);

    $this->actingAs($user)->get(route('job-postings.responses.index', $jobPosting))->assertOk();
    $this->actingAs($user)->get(route('job-postings.responses.show', [$jobPosting, $submission]))->assertOk();

    $this->actingAs($user)->patch(route('job-postings.responses.update-status', [$jobPosting, $submission]), [
        'status' => 'processed',
    ])->assertForbidden();

    $this->actingAs($user)->delete(route('job-postings.responses.destroy', [$jobPosting, $submission]))
        ->assertForbidden();

    $this->assertDatabaseHas('form_submissions', ['id' => $submission->id, 'status' => 'submitted']);
});

test('admins can update the status of a response', function () {
    [$user, $jobPosting, $form] = responseContext();
    $submission = FormSubmission::factory()->create([
        'form_id' => $form->id,
        'job_posting_id' => $jobPosting->id,
    ]);

    $this->actingAs($user)->patch(route('job-postings.responses.update-status', [$jobPosting, $submission]), [
        'status' => 'processed',
    ])->assertRedirect();

    expect($submission->fresh()->status)->toBe('processed');
});

test('updating a response status validates the status', function () {
    [$user, $jobPosting, $form] = responseContext();
    $submission = FormSubmission::factory()->create([
        'form_id' => $form->id,
        'job_posting_id' => $jobPosting->id,
    ]);

    $this->actingAs($user)->patch(route('job-postings.responses.update-status', [$jobPosting, $submission]), [
        'status' => 'archived',
    ])->assertSessionHasErrors('status');
});

test('admins can delete a response and its answers', function () {
    [$user, $jobPosting, $form] = responseContext();
    $submission = FormSubmission::factory()->create([
        'form_id' => $form->id,
        'job_posting_id' => $jobPosting->id,
    ]);
    $field = FormField::factory()->create(['form_id' => $form->id]);
    $answer = FormAnswer::factory()->create([
        'form_submission_id' => $submission->id,
        'form_field_id' => $field->id,
    ]);

    $this->actingAs($user)->delete(route('job-postings.responses.destroy', [$jobPosting, $submission]))
        ->assertRedirectToRoute('job-postings.responses.index', $jobPosting);

    $this->assertDatabaseMissing('form_submissions', ['id' => $submission->id]);
    $this->assertDatabaseMissing('form_answers', ['id' => $answer->id]);
});

test('a response cannot be opened through a different job posting', function () {
    [$user, $jobPosting, $form] = responseContext();
    $otherJobPosting = JobPosting::factory()->create([
        'company_id' => $user->company_id,
        'form_id' => $form->id,
    ]);
    $submission = FormSubmission::factory()->create([
        'form_id' => $form->id,
        'job_posting_id' => $otherJobPosting->id,
    ]);

    $this->actingAs($user)->get(route('job-postings.responses.show', [$jobPosting, $submission]))
        ->assertNotFound();
});

test('users cannot open responses for another company', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $jobPosting = JobPosting::factory()->create();
    $form = Form::factory()->create(['company_id' => $jobPosting->company_id]);
    $jobPosting->update(['form_id' => $form->id]);
    $submission = FormSubmission::factory()->create([
        'form_id' => $form->id,
        'job_posting_id' => $jobPosting->id,
    ]);

    $this->actingAs($user)->get(route('job-postings.responses.index', $jobPosting))->assertForbidden();
    $this->actingAs($user)->get(route('job-postings.responses.show', [$jobPosting, $submission]))->assertForbidden();
    $this->actingAs($user)->patch(route('job-postings.responses.update-status', [$jobPosting, $submission]), [
        'status' => 'processed',
    ])->assertForbidden();
    $this->actingAs($user)->delete(route('job-postings.responses.destroy', [$jobPosting, $submission]))
        ->assertForbidden();
});
