<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSection;
use App\Models\User;
use App\Repositories\FormRepository;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormService
{
    /**
     * Statuses a form may hold.
     *
     * @var list<string>
     */
    public const STATUSES = ['draft', 'published', 'closed'];

    /**
     * Field types supported by the form builder.
     *
     * @var list<string>
     */
    public const FIELD_TYPES = [
        'short_text',
        'paragraph',
        'multiple_choice',
        'checkboxes',
        'dropdown',
        'file_upload',
        'date',
        'time',
        'email',
        'number',
        'url',
        'phone',
        'linear_scale',
    ];

    /**
     * Field types that use a list of options.
     *
     * @var list<string>
     */
    public const CHOICE_TYPES = ['multiple_choice', 'checkboxes', 'dropdown'];

    public function __construct(private FormRepository $repository) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, array $attributes): Form
    {
        if ($user->company_id === null) {
            throw new DomainException('You must belong to a company before creating a form.');
        }

        $attributes = $this->prepareMetadata($attributes);
        $attributes['company_id'] = $user->company_id;
        $attributes['created_by'] = $user->id;
        $attributes['public_token'] ??= $this->newPublicToken();

        if ($attributes['status'] === 'published') {
            $attributes['published_at'] = now();
        }

        return $this->repository->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, Form $form, array $attributes): Form
    {
        $this->ensureCompanyMatches($user, $form);
        $form = $this->ensurePublicToken($form);

        $attributes = $this->prepareMetadata($attributes, $form->settings ?? []);

        if ($attributes['status'] === 'published' && $form->published_at === null) {
            $attributes['published_at'] = now();
        }

        return $this->repository->update($form, $attributes);
    }

    public function updateStatus(User $user, Form $form, string $status): Form
    {
        $this->ensureCompanyMatches($user, $form);
        $form = $this->ensurePublicToken($form);

        if (! in_array($status, self::STATUSES, true)) {
            throw new DomainException('The selected form status is invalid.');
        }

        $attributes = ['status' => $status];

        if ($status === 'published' && $form->published_at === null) {
            $attributes['published_at'] = now();
        }

        return $this->repository->update($form, $attributes);
    }

    /**
     * Delete a form and any responses already collected through it.
     */
    public function delete(User $user, Form $form): int
    {
        $this->ensureCompanyMatches($user, $form);

        return DB::transaction(function () use ($form): int {
            $deletedSubmissions = $form->submissions()->delete();

            $form->delete();

            return $deletedSubmissions;
        });
    }

    /**
     * Replace the section, field, and option structure of a form.
     *
     * @param  array<int, array<string, mixed>>  $sections
     */
    public function syncSchema(User $user, Form $form, array $sections): Form
    {
        $this->ensureCompanyMatches($user, $form);

        $existingFieldIds = $form->fields()->pluck('id')->all();

        $inputFieldIds = collect($sections)
            ->flatMap(fn (array $section): array => collect($section['fields'] ?? [])
                ->pluck('id')
                ->filter()
                ->all())
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $removedFieldIds = array_values(array_diff($existingFieldIds, $inputFieldIds));

        if ($removedFieldIds !== [] && $form->fields()->whereIntegerInRaw('id', $removedFieldIds)->whereHas('answers')->exists()) {
            throw new DomainException('A question that already has responses cannot be removed. Keep it in the form.');
        }

        DB::transaction(function () use ($form, $sections, $removedFieldIds): void {
            $keptSectionIds = [];

            foreach (array_values($sections) as $sectionIndex => $sectionData) {
                $section = null;

                if (! empty($sectionData['id'])) {
                    $section = $form->sections()->lockForUpdate()->find((int) $sectionData['id']);

                    if ($section === null) {
                        throw new DomainException('A section does not belong to this form.');
                    }
                }

                $attributes = [
                    'title' => $sectionData['title'],
                    'description' => $sectionData['description'] ?? null,
                    'sort_order' => $sectionIndex,
                ];

                if ($section === null) {
                    $section = $form->sections()->create($attributes);
                } else {
                    $section->update($attributes);
                }

                $keptSectionIds[] = $section->id;

                $this->syncFields($form, $section, $sectionData['fields'] ?? []);
            }

            if ($removedFieldIds !== []) {
                $form->fields()->whereIntegerInRaw('id', $removedFieldIds)->delete();
            }

            $form->sections()->whereNotIn('id', $keptSectionIds)->delete();
        });

        $form->increment('version');

        return $form->fresh(['sections.fields.options']);
    }

    private function ensureCompanyMatches(User $user, Form $form): void
    {
        if ($user->company_id === null || $user->company_id !== $form->company_id) {
            throw new DomainException('This form does not belong to your company.');
        }
    }

    private function ensurePublicToken(Form $form): Form
    {
        if (blank($form->public_token)) {
            $form->update(['public_token' => $this->newPublicToken()]);
        }

        return $form;
    }

    private function newPublicToken(): string
    {
        do {
            $token = Str::random(40);
        } while (Form::query()->where('public_token', $token)->exists());

        return $token;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $existingSettings
     * @return array<string, mixed>
     */
    private function prepareMetadata(array $attributes, array $existingSettings = []): array
    {
        $settings = $existingSettings;

        if (array_key_exists('confirmation_message', $attributes)) {
            $message = trim((string) ($attributes['confirmation_message'] ?? ''));

            if ($message !== '') {
                $settings['confirmation_message'] = $message;
            } else {
                unset($settings['confirmation_message']);
            }

            unset($attributes['confirmation_message']);
        }

        $attributes['settings'] = $settings === [] ? null : $settings;

        return $attributes;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function syncFields(Form $form, FormSection $section, array $fields): void
    {
        foreach (array_values($fields) as $index => $fieldData) {
            $field = null;

            if (! empty($fieldData['id'])) {
                $field = $form->fields()->find((int) $fieldData['id']);

                if ($field === null) {
                    throw new DomainException('A field does not belong to this form.');
                }
            }

            $explicitKey = trim((string) ($fieldData['field_key'] ?? ''));

            if ($explicitKey !== '') {
                $keyTaken = $form->fields()
                    ->where('field_key', $explicitKey)
                    ->when($field !== null, fn ($query) => $query->whereKeyNot($field->id))
                    ->exists();

                if ($keyTaken) {
                    throw new DomainException('Two questions have the same answer key. Give each question a different answer key.');
                }

                $fieldKey = $explicitKey;
            } else {
                $fieldKey = $this->uniqueFieldKey($form, $fieldData['label'], $field?->id);
            }

            $attributes = [
                'form_section_id' => $section->id,
                'field_key' => $fieldKey,
                'label' => $fieldData['label'],
                'type' => $fieldData['type'],
                'help_text' => $fieldData['help_text'] ?? null,
                'placeholder' => $fieldData['placeholder'] ?? null,
                'is_required' => filter_var($fieldData['is_required'] ?? false, FILTER_VALIDATE_BOOL),
                'maps_to' => $fieldData['maps_to'] ?? null,
                'sort_order' => $index,
            ];

            if ($field === null) {
                $field = $form->fields()->create($attributes);
            } else {
                $field->update($attributes);
            }

            $this->syncOptions($field, $fieldData['type'], $fieldData['options'] ?? []);
        }
    }

    private function uniqueFieldKey(Form $form, string $label, ?int $ignoreId = null): string
    {
        $base = Str::substr(Str::slug($label, '_') ?: 'field', 0, 220);
        $key = $base;
        $suffix = 2;

        while ($form->fields()
            ->where('field_key', $key)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $key = $base.'_'.$suffix++;
        }

        return $key;
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    private function syncOptions(FormField $field, string $type, array $options): void
    {
        $field->options()->delete();

        if (! in_array($type, self::CHOICE_TYPES, true)) {
            return;
        }

        $usedValues = [];

        foreach (array_values($options) as $index => $option) {
            $label = trim((string) ($option['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $value = trim((string) ($option['value'] ?? ''));
            $value = $value !== '' ? Str::slug($value, '_') : Str::slug($label, '_');
            $value = $value !== '' ? $value : 'option';

            $base = $value;
            $suffix = 2;

            while (in_array($value, $usedValues, true)) {
                $value = $base.'_'.$suffix++;
            }

            $usedValues[] = $value;

            $field->options()->create([
                'label' => $label,
                'value' => $value,
                'sort_order' => $index,
            ]);
        }
    }
}
