<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Candidate;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicApplicationService
{
    /**
     * Create the candidate, application, submission, and answers for a public form.
     *
     * @param  array<int|string, mixed>  $answers
     */
    public function submit(Form $form, ?JobPosting $jobPosting, array $answers, Request $request): FormSubmission
    {
        $form->loadMissing(['sections.fields.options', 'fields.options']);

        return DB::transaction(function () use ($form, $jobPosting, $answers, $request): FormSubmission {
            $candidateData = $this->candidateData($form, $answers);
            $candidate = isset($candidateData['email'])
                ? $this->upsertCandidate($form, $candidateData)
                : null;

            $application = $jobPosting === null || $candidate === null ? null : Application::query()->create([
                'company_id' => $form->company_id,
                'candidate_id' => $candidate->id,
                'job_id' => $jobPosting->id,
                'source' => 'form',
                'status' => 'received',
                'submitted_at' => now(),
            ]);

            $submission = FormSubmission::query()->create([
                'form_id' => $form->id,
                'job_posting_id' => $jobPosting?->id,
                'candidate_id' => $candidate?->id,
                'application_id' => $application?->id,
                'status' => 'submitted',
                'schema_snapshot' => $this->schemaSnapshot($form),
                'meta' => [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'referrer' => $request->headers->get('referer'),
                    'respondent_name' => $candidateData['full_name'] ?? null,
                    'respondent_email' => $candidateData['email'] ?? null,
                ],
                'submitted_at' => now(),
            ]);

            foreach ($form->fields as $field) {
                $this->storeAnswer($submission, $field, $answers[$field->id] ?? $answers[(string) $field->id] ?? null);
            }

            return $submission;
        });
    }

    /**
     * Extract candidate details from fields mapped to candidate attributes.
     *
     * @param  array<int|string, mixed>  $answers
     * @return array<string, string>
     */
    private function candidateData(Form $form, array $answers): array
    {
        $mappings = [
            'candidate.full_name' => 'full_name',
            'candidate.email' => 'email',
            'candidate.phone' => 'phone',
            'candidate.linkedin_url' => 'linkedin_url',
            'candidate.portfolio_url' => 'portfolio_url',
        ];

        $keyFallbacks = [
            'full_name' => 'full_name',
            'name' => 'full_name',
            'email' => 'email',
            'phone' => 'phone',
            'linkedin_url' => 'linkedin_url',
            'portfolio_url' => 'portfolio_url',
        ];

        $data = [];

        foreach ($form->fields as $field) {
            $value = $answers[$field->id] ?? $answers[(string) $field->id] ?? null;

            if (! is_scalar($value)) {
                continue;
            }

            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            $mapping = $mappings[$field->maps_to] ?? $keyFallbacks[$field->field_key] ?? null;

            if ($mapping === null) {
                continue;
            }

            $data[$mapping] ??= $value;
        }

        if (empty($data['email'])) {
            $emailField = $form->fields->first(fn (FormField $field): bool => $field->type === 'email')
                ?? $form->fields->first(fn (FormField $field): bool => Str::contains(Str::lower($field->field_key), 'email'));

            $value = $emailField === null
                ? null
                : ($answers[$emailField->id] ?? $answers[(string) $emailField->id] ?? null);

            if (is_scalar($value) && trim((string) $value) !== '') {
                $data['email'] = trim((string) $value);
            }
        }

        if (empty($data['email'])) {
            return $data;
        }

        if (empty($data['full_name'])) {
            $data['full_name'] = Str::headline(Str::before($data['email'], '@'));
        }

        return $data;
    }

    /**
     * @param  array<string, string>  $data
     */
    private function upsertCandidate(Form $form, array $data): Candidate
    {
        $candidate = Candidate::query()
            ->where('company_id', $form->company_id)
            ->where('email', $data['email'])
            ->first();

        if ($candidate === null) {
            return Candidate::query()->create([
                'company_id' => $form->company_id,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'linkedin_url' => $data['linkedin_url'] ?? null,
                'portfolio_url' => $data['portfolio_url'] ?? null,
                'source' => 'form',
                'source_meta' => ['form_id' => $form->id],
            ]);
        }

        $candidate->fill(array_filter([
            'full_name' => $data['full_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'linkedin_url' => $data['linkedin_url'] ?? null,
            'portfolio_url' => $data['portfolio_url'] ?? null,
        ], fn (mixed $value): bool => $value !== null && $value !== ''));

        $candidate->save();

        return $candidate;
    }

    private function storeAnswer(FormSubmission $submission, FormField $field, mixed $value): void
    {
        if ($value instanceof UploadedFile) {
            $filePath = $value->store('form-submissions/'.$submission->id, 'local');

            if ($filePath === false) {
                throw ValidationException::withMessages([
                    'answers' => 'A file could not be uploaded. Please try again.',
                ]);
            }

            $answer = $submission->answers()->create([
                'form_field_id' => $field->id,
                'value' => [$value->getClientOriginalName()],
            ]);

            $document = $this->createApplicationDocument($submission, $field, $value, $filePath);

            $answer->files()->create([
                'application_document_id' => $document?->id,
                'file_name' => $value->getClientOriginalName(),
                'file_path' => $filePath,
                'mime_type' => $value->getClientMimeType(),
                'size_bytes' => $value->getSize(),
            ]);

            return;
        }

        if (is_array($value)) {
            $value = array_values(array_filter(
                $value,
                fn (mixed $item): bool => $item !== null && $item !== '',
            ));

            if ($value === []) {
                return;
            }
        } elseif ($value === null || $value === '') {
            return;
        }

        if (in_array($field->type, ['number', 'linear_scale'], true) && is_numeric($value)) {
            $value = $field->type === 'linear_scale' ? (int) $value : (float) $value;
        }

        $submission->answers()->create([
            'form_field_id' => $field->id,
            'value' => $value,
        ]);
    }

    private function createApplicationDocument(FormSubmission $submission, FormField $field, UploadedFile $file, string $filePath): ?ApplicationDocument
    {
        if ($submission->application_id === null) {
            return null;
        }

        $type = $this->documentType($field);

        if ($type === null) {
            return null;
        }

        return ApplicationDocument::query()->create([
            'application_id' => $submission->application_id,
            'type' => $type,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'parsing_status' => 'pending',
        ]);
    }

    private function documentType(FormField $field): ?string
    {
        $key = Str::lower($field->field_key);

        return match (true) {
            Str::contains($key, ['resume', 'cv']) => 'resume',
            Str::contains($key, 'cover') => 'cover_letter',
            Str::contains($key, 'portfolio') => 'portfolio',
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function schemaSnapshot(Form $form): array
    {
        $fields = fn ($fields) => $fields->map(fn (FormField $field): array => [
            'field_key' => $field->field_key,
            'label' => $field->label,
            'type' => $field->type,
            'is_required' => $field->is_required,
        ])->values()->all();

        return [
            'version' => $form->version,
            'fields' => $fields($form->fields),
            'sections' => $form->sections->map(fn ($section): array => [
                'title' => $section->title,
                'fields' => $fields($section->fields),
            ])->values()->all(),
        ];
    }
}
