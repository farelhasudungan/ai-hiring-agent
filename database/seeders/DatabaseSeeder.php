<?php

namespace Database\Seeders;

use App\Models\AiScreening;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Form;
use App\Models\FormAnswer;
use App\Models\FormAnswerFile;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormSection;
use App\Models\FormSubmission;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = Company::factory()->create([
            'name' => 'Demo Company',
            'slug' => 'demo-company',
            'plan' => 'free',
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'admin',
        ]);

        $job = JobPosting::factory()->for($company)->create([
            'title' => 'Senior Laravel Developer',
            'status' => 'open',
        ]);

        $candidate = Candidate::factory()->for($company)->create([
            'full_name' => 'Jane Candidate',
            'email' => 'jane@example.com',
            'source' => 'form',
        ]);

        $application = Application::factory()->for($company)->create([
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'source' => 'form',
        ]);

        $document = ApplicationDocument::factory()->for($application)->create([
            'file_path' => 'applications/jane-candidate/resume.pdf',
            'parsing_status' => 'success',
            'parsed_text' => 'Jane is a senior Laravel developer with five years of experience.',
        ]);

        $form = Form::factory()->for($company)->create([
            'created_by' => $admin->id,
            'title' => 'Senior Laravel Developer Application',
            'description' => 'Tell us about yourself and your Laravel experience.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $job->update(['form_id' => $form->id]);

        $personalSection = FormSection::factory()->for($form)->create([
            'title' => 'Personal Information',
            'sort_order' => 1,
        ]);

        $experienceSection = FormSection::factory()->for($form)->create([
            'title' => 'Experience',
            'sort_order' => 2,
        ]);

        $fullNameField = FormField::factory()->for($form)->create([
            'form_section_id' => $personalSection->id,
            'field_key' => 'full_name',
            'label' => 'Full name',
            'type' => 'short_text',
            'is_required' => true,
            'maps_to' => 'candidate.full_name',
            'sort_order' => 1,
        ]);

        $emailField = FormField::factory()->for($form)->create([
            'form_section_id' => $personalSection->id,
            'field_key' => 'email',
            'label' => 'Email address',
            'type' => 'email',
            'is_required' => true,
            'maps_to' => 'candidate.email',
            'sort_order' => 2,
        ]);

        $phoneField = FormField::factory()->for($form)->create([
            'form_section_id' => $personalSection->id,
            'field_key' => 'phone',
            'label' => 'Phone number',
            'type' => 'phone',
            'is_required' => false,
            'maps_to' => 'candidate.phone',
            'sort_order' => 3,
        ]);

        $resumeField = FormField::factory()->for($form)->create([
            'form_section_id' => $personalSection->id,
            'field_key' => 'resume',
            'label' => 'Upload your resume',
            'type' => 'file_upload',
            'is_required' => true,
            'validation' => [
                'mime_types' => ['application/pdf'],
                'max_size_kb' => 5120,
            ],
            'sort_order' => 4,
        ]);

        $experienceField = FormField::factory()->for($form)->create([
            'form_section_id' => $experienceSection->id,
            'field_key' => 'experience_summary',
            'label' => 'Summarize your relevant experience',
            'type' => 'paragraph',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $availabilityField = FormField::factory()->for($form)->create([
            'form_section_id' => $experienceSection->id,
            'field_key' => 'availability',
            'label' => 'When can you start?',
            'type' => 'dropdown',
            'is_required' => true,
            'sort_order' => 2,
        ]);

        foreach ([
            ['label' => 'Immediately', 'value' => 'immediately'],
            ['label' => 'Within 1 month', 'value' => 'within_1_month'],
            ['label' => 'More than 1 month', 'value' => 'more_than_1_month'],
        ] as $index => $option) {
            FormFieldOption::factory()->create([
                'form_field_id' => $availabilityField->id,
                'label' => $option['label'],
                'value' => $option['value'],
                'sort_order' => $index + 1,
            ]);
        }

        $submission = FormSubmission::factory()->for($form)->create([
            'job_posting_id' => $job->id,
            'candidate_id' => $candidate->id,
            'application_id' => $application->id,
            'status' => 'processed',
            'schema_snapshot' => [
                'version' => $form->version,
                'fields' => $form->fields()->get(['field_key', 'label', 'type'])->toArray(),
            ],
            'submitted_at' => now(),
        ]);

        FormAnswer::factory()->create([
            'form_submission_id' => $submission->id,
            'form_field_id' => $fullNameField->id,
            'value' => $candidate->full_name,
        ]);

        FormAnswer::factory()->create([
            'form_submission_id' => $submission->id,
            'form_field_id' => $emailField->id,
            'value' => $candidate->email,
        ]);

        FormAnswer::factory()->create([
            'form_submission_id' => $submission->id,
            'form_field_id' => $phoneField->id,
            'value' => $candidate->phone,
        ]);

        $resumeAnswer = FormAnswer::factory()->create([
            'form_submission_id' => $submission->id,
            'form_field_id' => $resumeField->id,
            'value' => null,
        ]);

        FormAnswerFile::factory()->create([
            'form_answer_id' => $resumeAnswer->id,
            'application_document_id' => $document->id,
            'file_name' => $document->file_name,
            'file_path' => $document->file_path,
            'mime_type' => $document->mime_type,
            'size_bytes' => $document->size_bytes,
        ]);

        FormAnswer::factory()->create([
            'form_submission_id' => $submission->id,
            'form_field_id' => $experienceField->id,
            'value' => 'Five years of Laravel experience across two product teams.',
        ]);

        FormAnswer::factory()->create([
            'form_submission_id' => $submission->id,
            'form_field_id' => $availabilityField->id,
            'value' => 'within_1_month',
        ]);

        $submission->load('answers.field');

        AiScreening::factory()->for($application)->create([
            'job_id' => $job->id,
            'form_submission_id' => $submission->id,
            'document_id' => $document->id,
            'status' => 'success',
            'input_snapshot' => [
                'form' => [
                    'id' => $form->id,
                    'version' => $form->version,
                    'submission_id' => $submission->id,
                    'answers' => $submission->answers
                        ->mapWithKeys(fn (FormAnswer $answer) => [$answer->field->field_key => $answer->value])
                        ->all(),
                ],
                'documents' => [
                    [
                        'id' => $document->id,
                        'type' => $document->type,
                        'file_name' => $document->file_name,
                        'parsed_text_length' => strlen((string) $document->parsed_text),
                    ],
                ],
                'job' => [
                    'id' => $job->id,
                    'required_skills' => $job->required_skills,
                    'nice_to_have' => $job->nice_to_have,
                    'min_experience_years' => $job->min_experience_years,
                ],
            ],
            'match_score' => 87,
            'recommendation' => 'strong_match',
            'summary' => 'Strong backend match with the required Laravel and database skills.',
            'candidate_skills' => ['PHP', 'Laravel', 'MySQL', 'Docker'],
            'matched_skills' => ['PHP', 'Laravel', 'MySQL'],
            'missing_skills' => ['AWS'],
            'experience_years' => 5,
            'education_level' => 'bachelor',
            'started_at' => now()->subSeconds(12),
            'completed_at' => now(),
            'duration_ms' => 12_000,
        ]);
    }
}
