<?php

namespace App\Http\Requests;

use App\Models\Form;
use App\Services\FormService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFormSchemaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $form = $this->route('form');

        return $form instanceof Form
            && ($this->user()?->can('update', $form) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sections' => ['present', 'array'],
            'sections.*.id' => ['nullable', 'integer'],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.description' => ['nullable', 'string'],
            'sections.*.fields' => ['sometimes', 'array'],
            'sections.*.fields.*.id' => ['nullable', 'integer'],
            'sections.*.fields.*.field_key' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]+$/'],
            'sections.*.fields.*.label' => ['required', 'string', 'max:255'],
            'sections.*.fields.*.type' => ['required', Rule::in(FormService::FIELD_TYPES)],
            'sections.*.fields.*.help_text' => ['nullable', 'string'],
            'sections.*.fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'sections.*.fields.*.is_required' => ['sometimes', 'boolean'],
            'sections.*.fields.*.maps_to' => ['nullable', 'string', 'max:255'],
            'sections.*.fields.*.options' => ['sometimes', 'array'],
            'sections.*.fields.*.options.*.label' => ['required', 'string', 'max:255'],
            'sections.*.fields.*.options.*.value' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Remove option rows from field types that do not use options, so hidden
     * rows cannot block the save with invisible validation errors.
     */
    protected function prepareForValidation(): void
    {
        $sections = $this->input('sections');

        if (! is_array($sections)) {
            return;
        }

        foreach ($sections as $sectionIndex => $section) {
            if (! is_array($section)) {
                continue;
            }

            $fields = $section['fields'] ?? null;

            if (! is_array($fields)) {
                continue;
            }

            foreach ($fields as $fieldIndex => $field) {
                if (! is_array($field)) {
                    continue;
                }

                if (! in_array($field['type'] ?? null, FormService::CHOICE_TYPES, true)) {
                    $sections[$sectionIndex]['fields'][$fieldIndex]['options'] = [];
                }
            }
        }

        $this->merge(['sections' => $sections]);
    }

    /**
     * Get custom validation messages written for the form builder.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sections.required' => 'Add at least one section before saving the form.',
            'sections.array' => 'Add at least one section before saving the form.',
            'sections.min' => 'Add at least one section before saving the form.',
            'sections.*.title.required' => 'Give this section a title.',
            'sections.*.title.max' => 'This section title is too long.',
            'sections.*.fields.*.label.required' => 'Give this question a label.',
            'sections.*.fields.*.label.max' => 'This question label is too long.',
            'sections.*.fields.*.type.required' => 'Choose a question type.',
            'sections.*.fields.*.type.in' => 'Choose a valid question type.',
            'sections.*.fields.*.field_key.max' => 'This answer key is too long.',
            'sections.*.fields.*.field_key.regex' => 'Answer keys can only use letters, numbers, and underscores.',
            'sections.*.fields.*.placeholder.max' => 'This placeholder is too long.',
            'sections.*.fields.*.maps_to.max' => 'This field mapping is too long.',
            'sections.*.fields.*.options.*.label.required' => 'Give this option a label, or remove the empty option.',
            'sections.*.fields.*.options.*.label.max' => 'This option label is too long.',
            'sections.*.fields.*.options.*.value.max' => 'This option value is too long.',
        ];
    }

    /**
     * Ensure nested sections and fields belong to the form being edited.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $form = $this->route('form');

                if (! $form instanceof Form) {
                    return;
                }

                $sections = $this->input('sections');

                if (! is_array($sections)) {
                    return;
                }

                foreach ($sections as $sectionIndex => $section) {
                    if (! is_array($section)) {
                        continue;
                    }

                    $sectionId = $section['id'] ?? null;

                    if ($sectionId !== null && ! $form->sections()->whereKey($sectionId)->exists()) {
                        $validator->errors()->add("sections.{$sectionIndex}.id", 'This section does not belong to the form.');
                    }

                    $fields = $section['fields'] ?? [];

                    if (! is_array($fields)) {
                        continue;
                    }

                    foreach ($fields as $fieldIndex => $field) {
                        if (! is_array($field)) {
                            continue;
                        }

                        $fieldId = $field['id'] ?? null;

                        if ($fieldId !== null && ! $form->fields()->whereKey($fieldId)->exists()) {
                            $validator->errors()->add("sections.{$sectionIndex}.fields.{$fieldIndex}.id", 'This field does not belong to the form.');
                        }
                    }
                }
            },
        ];
    }
}
