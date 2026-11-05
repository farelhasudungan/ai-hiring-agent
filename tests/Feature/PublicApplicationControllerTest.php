<?php

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Form;
use App\Models\FormAnswer;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormSection;
use App\Models\FormSubmission;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/**
 * @return array{0: Form, 1: JobPosting, 2: FormSection, 3: FormField, 4: FormField}
 */
function publicApplicationContext(array $formAttributes = []): array
{
    $user = User::factory()->create(['role' => 'admin']);

    $form = Form::factory()->create(array_merge([
        'company_id' => $user->company_id,
        'status' => 'published',
        'closes_at' => null,
    ], $formAttributes));

    $jobPosting = JobPosting::factory()->create([
        'company_id' => $user->company_id,
        'form_id' => $form->id,
        'status' => 'open',
    ]);

    $section = FormSection::factory()->create([
        'form_id' => $form->id,
        'title' => 'Personal information',
        'sort_order' => 1,
    ]);

    $nameField = FormField::factory()->create([
        'form_id' => $form->id,
        'form_section_id' => $section->id,
        'field_key' => 'full_name',
        'label' => 'Full name',
        'type' => 'short_text',
        'is_required' => true,
        'maps_to' => 'candidate.full_name',
        'sort_order' => 1,
    ]);

    $emailField = FormField::factory()->create([
        'form_id' => $form->id,
        'form_section_id' => $section->id,
        'field_key' => 'email',
        'label' => 'Email address',
        'type' => 'email',
        'is_required' => true,
        'maps_to' => 'candidate.email',
        'sort_order' => 2,
    ]);

    return [$form, $jobPosting, $section, $nameField, $emailField];
}

test('published forms show a public application link on the form page', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create([
        'company_id' => $user->company_id,
        'status' => 'published',
        'public_token' => 'public-form-token',
    ]);

    $this->actingAs($user)->get(route('forms.show', $form))
        ->assertOk()
        ->assertSee('Public application link')
        ->assertSee(route('public.apply.show', ['token' => $form->public_token]), false);
});

test('draft forms ask the manager to publish before sharing a link', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create([
        'company_id' => $user->company_id,
        'status' => 'draft',
    ]);

    $this->actingAs($user)->get(route('forms.show', $form))
        ->assertOk()
        ->assertSee('Publish this form to generate a public application link.')
        ->assertDontSee(route('public.apply.show', ['token' => $form->public_token]), false);
});

test('creating a form generates a stable public token', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)->post(route('forms.store'), [
        'title' => 'Public form',
        'status' => 'draft',
    ])->assertRedirect();

    $form = Form::query()->sole();
    $token = $form->public_token;

    expect($token)->toBeString()->not->toBe('');

    $this->actingAs($user)->patch(route('forms.update-status', $form), ['status' => 'published'])
        ->assertRedirect();

    expect($form->fresh()->public_token)->toBe($token);
});

test('publishing a legacy form generates its public token', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create([
        'company_id' => $user->company_id,
        'status' => 'draft',
        'public_token' => null,
    ]);

    $this->actingAs($user)->patch(route('forms.update-status', $form), ['status' => 'published'])
        ->assertRedirect();

    expect($form->fresh()->public_token)->toBeString()->not->toBe('');
});

test('applicants can open a published form without logging in', function () {
    [$form, $jobPosting] = publicApplicationContext();

    $this->get(route('public.apply.show', $form->public_token))
        ->assertOk()
        ->assertSee($form->title)
        ->assertSee($jobPosting->title)
        ->assertSee('Full name')
        ->assertSee('Email address');
});

test('a public form for multiple roles asks the applicant to choose one', function () {
    [$form, $jobPosting] = publicApplicationContext();

    $otherJobPosting = JobPosting::factory()->create([
        'company_id' => $form->company_id,
        'form_id' => $form->id,
        'title' => 'Second open role',
    ]);

    $this->get(route('public.apply.show', $form->public_token))
        ->assertOk()
        ->assertSee('Choose the role you would like to apply for.')
        ->assertSee($jobPosting->title)
        ->assertSee($otherJobPosting->title)
        ->assertDontSee('Full name');

    $this->get(route('public.apply.show', [
        'token' => $form->public_token,
        'job' => $otherJobPosting->id,
    ]))
        ->assertOk()
        ->assertSee($otherJobPosting->title)
        ->assertSee('Full name');
});

test('a public form rejects job postings that do not use it', function () {
    [$form] = publicApplicationContext();

    $foreignJobPosting = JobPosting::factory()->create();

    $this->get(route('public.apply.show', [
        'token' => $form->public_token,
        'job' => $foreignJobPosting->id,
    ]))->assertNotFound();
});

test('draft closed and expired forms are not accepting responses', function () {
    $draft = Form::factory()->create(['status' => 'draft']);
    $closed = Form::factory()->create(['status' => 'closed']);
    $expired = Form::factory()->create([
        'status' => 'published',
        'closes_at' => now()->subDay(),
    ]);

    foreach ([$draft, $closed, $expired] as $form) {
        $this->get(route('public.apply.show', $form->public_token))
            ->assertStatus(410)
            ->assertSee('not accepting new responses');
    }
});

test('unknown public form links are not found', function () {
    $this->get(route('public.apply.show', 'unknown-token'))->assertNotFound();
});

test('an applicant can submit a public application', function () {
    [$form, $jobPosting, , $nameField, $emailField] = publicApplicationContext();

    $response = $this->post(route('public.apply.store', ['token' => $form->public_token]), [
        'answers' => [
            $nameField->id => 'Ada Lovelace',
            $emailField->id => 'ada@example.com',
        ],
    ]);

    $response->assertRedirect(route('public.apply.success', [
        'token' => $form->public_token,
        'job' => $jobPosting->id,
    ]));

    $candidate = Candidate::query()->where('email', 'ada@example.com')->sole();

    expect($candidate->company_id)->toBe($form->company_id)
        ->and($candidate->full_name)->toBe('Ada Lovelace')
        ->and($candidate->source)->toBe('form');

    $application = Application::query()->where('candidate_id', $candidate->id)->sole();

    expect($application->job_id)->toBe($jobPosting->id)
        ->and($application->company_id)->toBe($form->company_id)
        ->and($application->source)->toBe('form')
        ->and($application->status)->toBe('received')
        ->and($application->submitted_at)->not->toBeNull();

    $submission = FormSubmission::query()->where('form_id', $form->id)->sole();

    expect($submission->job_posting_id)->toBe($jobPosting->id)
        ->and($submission->candidate_id)->toBe($candidate->id)
        ->and($submission->application_id)->toBe($application->id)
        ->and($submission->status)->toBe('submitted')
        ->and($submission->submitted_at)->not->toBeNull()
        ->and($submission->schema_snapshot['version'])->toBe($form->version)
        ->and($submission->meta['ip'])->not->toBeNull();

    $this->assertDatabaseHas('form_answers', [
        'form_submission_id' => $submission->id,
        'form_field_id' => $nameField->id,
    ]);

    $this->assertDatabaseHas('form_answers', [
        'form_submission_id' => $submission->id,
        'form_field_id' => $emailField->id,
    ]);
});

test('a public submission is associated with the selected role', function () {
    [$form, $jobPosting] = publicApplicationContext([
        'title' => 'Shared application form',
    ]);

    $secondJobPosting = JobPosting::factory()->create([
        'company_id' => $form->company_id,
        'form_id' => $form->id,
        'title' => 'Second role',
    ]);

    [$nameField, $emailField] = $form->fields()->orderBy('sort_order')->get()->all();

    $this->post(route('public.apply.store', [
        'token' => $form->public_token,
        'job' => $secondJobPosting->id,
    ]), [
        'answers' => [
            $nameField->id => 'Grace Hopper',
            $emailField->id => 'grace@example.com',
        ],
    ])->assertRedirect(route('public.apply.success', [
        'token' => $form->public_token,
        'job' => $secondJobPosting->id,
    ]));

    expect(Application::query()->sole()->job_id)->toBe($secondJobPosting->id)
        ->and(FormSubmission::query()->sole()->job_posting_id)->toBe($secondJobPosting->id);
});

test('an existing candidate is reused when the same email applies again', function () {
    [$form, , , $nameField, $emailField] = publicApplicationContext();

    $candidate = Candidate::factory()->create([
        'company_id' => $form->company_id,
        'full_name' => 'Old Name',
        'email' => 'ada@example.com',
        'phone' => null,
    ]);

    $this->post(route('public.apply.store', $form->public_token), [
        'answers' => [
            $nameField->id => 'Ada Lovelace',
            $emailField->id => 'ada@example.com',
        ],
    ])->assertRedirect();

    expect(Candidate::query()->count())->toBe(1)
        ->and($candidate->fresh()->full_name)->toBe('Ada Lovelace');
});

test('public submissions validate required and typed answers', function () {
    [$form, , , $nameField, $emailField] = publicApplicationContext();

    $this->from(route('public.apply.show', $form->public_token))
        ->post(route('public.apply.store', $form->public_token), [
            'answers' => [
                $nameField->id => '',
                $emailField->id => 'not-an-email',
            ],
        ])
        ->assertRedirect(route('public.apply.show', $form->public_token))
        ->assertSessionHasErrors([
            'answers.'.$nameField->id,
            'answers.'.$emailField->id,
        ]);

    $this->assertDatabaseCount('form_submissions', 0);
    $this->assertDatabaseCount('candidates', 0);
});

test('public validation errors are shown inline on the form', function () {
    [$form, , , $nameField, $emailField] = publicApplicationContext();

    $this->from(route('public.apply.show', $form->public_token))
        ->followingRedirects()
        ->post(route('public.apply.store', $form->public_token), [
            'answers' => [
                $nameField->id => '',
                $emailField->id => 'not-an-email',
            ],
        ])
        ->assertOk()
        ->assertSee('Please check the highlighted questions and try again.')
        ->assertSee('Please answer', false)
        ->assertSee('Enter a valid email address', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('data-field-error', false)
        ->assertSee('border-red-700', false);
});

test('public submissions validate choice options', function () {
    [$form, , $section, $nameField, $emailField] = publicApplicationContext();

    $choiceField = FormField::factory()->create([
        'form_id' => $form->id,
        'form_section_id' => $section->id,
        'field_key' => 'availability',
        'label' => 'Availability',
        'type' => 'dropdown',
        'is_required' => true,
        'sort_order' => 3,
    ]);

    FormFieldOption::factory()->create([
        'form_field_id' => $choiceField->id,
        'label' => 'Immediately',
        'value' => 'immediately',
    ]);

    $this->post(route('public.apply.store', $form->public_token), [
        'answers' => [
            $nameField->id => 'Ada Lovelace',
            $emailField->id => 'ada@example.com',
            $choiceField->id => 'not-an-option',
        ],
    ])->assertSessionHasErrors('answers.'.$choiceField->id);

    $this->assertDatabaseCount('form_submissions', 0);
});

test('public file uploads are stored and attached to the application', function () {
    Storage::fake('local');

    [$form, , $section, $nameField, $emailField] = publicApplicationContext();

    $resumeField = FormField::factory()->create([
        'form_id' => $form->id,
        'form_section_id' => $section->id,
        'field_key' => 'resume',
        'label' => 'Resume',
        'type' => 'file_upload',
        'is_required' => true,
        'validation' => [
            'mime_types' => ['application/pdf'],
            'max_size_kb' => 5120,
        ],
        'sort_order' => 3,
    ]);

    $this->post(route('public.apply.store', $form->public_token), [
        'answers' => [
            $nameField->id => 'Ada Lovelace',
            $emailField->id => 'ada@example.com',
            $resumeField->id => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ],
    ])->assertRedirect();

    $answer = FormAnswer::query()->where('form_field_id', $resumeField->id)->sole();
    $answerFile = $answer->files()->sole();

    expect($answerFile->file_name)->toBe('resume.pdf')
        ->and($answerFile->application_document_id)->not->toBeNull();

    Storage::disk('local')->assertExists($answerFile->file_path);

    $this->assertDatabaseHas('application_documents', [
        'id' => $answerFile->application_document_id,
        'type' => 'resume',
        'file_name' => 'resume.pdf',
    ]);
});

test('a form without an email question still records a response without an application', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create(['company_id' => $user->company_id]);

    $jobPosting = JobPosting::factory()->create([
        'company_id' => $form->company_id,
        'form_id' => $form->id,
    ]);

    $field = FormField::factory()->create([
        'form_id' => $form->id,
        'field_key' => 'full_name',
        'label' => 'Full name',
        'type' => 'short_text',
        'is_required' => true,
        'maps_to' => 'candidate.full_name',
    ]);

    $this->post(route('public.apply.store', $form->public_token), [
        'answers' => [$field->id => 'Ada Lovelace'],
    ])->assertRedirect();

    $submission = FormSubmission::query()->sole();

    expect($submission->job_posting_id)->toBe($jobPosting->id)
        ->and($submission->candidate_id)->toBeNull()
        ->and($submission->application_id)->toBeNull()
        ->and($submission->meta['respondent_name'])->toBe('Ada Lovelace');

    $this->assertDatabaseCount('candidates', 0);
    $this->assertDatabaseCount('applications', 0);
    $this->assertDatabaseHas('form_answers', [
        'form_submission_id' => $submission->id,
        'form_field_id' => $field->id,
    ]);
});

test('an unmapped email question is used for the candidate', function () {
    [$form, , , $nameField, $emailField] = publicApplicationContext();

    $emailField->update([
        'field_key' => 'contact_email',
        'maps_to' => null,
    ]);

    $this->post(route('public.apply.store', $form->public_token), [
        'answers' => [
            $nameField->id => 'Ada Lovelace',
            $emailField->id => 'ada@example.com',
        ],
    ])->assertRedirect();

    $candidate = Candidate::query()->where('email', 'ada@example.com')->sole();

    expect($candidate->full_name)->toBe('Ada Lovelace')
        ->and(Application::query()->where('candidate_id', $candidate->id)->exists())->toBeTrue();
});

test('the public success page shows the confirmation message', function () {
    [$form] = publicApplicationContext();

    $this->get(route('public.apply.success', $form->public_token))
        ->assertOk()
        ->assertSee('Application received')
        ->assertSee($form->settings['confirmation_message']);
});

test('the job posting page shows its public application link when the form is live', function () {
    [$form, $jobPosting] = publicApplicationContext();

    $user = User::factory()->create([
        'company_id' => $form->company_id,
        'role' => 'admin',
    ]);

    $this->actingAs($user)->get(route('job-postings.show', $jobPosting))
        ->assertOk()
        ->assertSee('Public application link')
        ->assertSee(route('public.apply.show', [
            'token' => $form->public_token,
            'job' => $jobPosting->id,
        ]), false);
});
