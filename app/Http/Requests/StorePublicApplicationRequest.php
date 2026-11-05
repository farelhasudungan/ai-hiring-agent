<?php

namespace App\Http\Requests;

use App\Models\Form;
use App\Models\FormField;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicApplicationRequest extends FormRequest
{
    private ?Form $resolvedForm = null;

    private bool $formResolved = false;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $form = $this->form();

        if ($form === null) {
            return [];
        }

        $rules = [];

        foreach ($form->fields as $field) {
            $key = 'answers.'.$field->id;
            $fieldRules = [$field->is_required ? 'required' : 'nullable'];

            array_push($fieldRules, ...$this->rulesForField($field));

            $rules[$key] = $fieldRules;

            if ($field->type === 'checkboxes' && $field->options->isNotEmpty()) {
                $rules[$key.'.*'] = ['string', Rule::in($field->options->pluck('value')->all())];
            }
        }

        if ($rules !== []) {
            $rules['answers'] = ['required', 'array'];
        }

        return $rules;
    }

    /**
     * Get the validation rules for a single form field.
     *
     * @return list<mixed>
     */
    private function rulesForField(FormField $field): array
    {
        $validation = $field->validation ?? [];

        $rules = match ($field->type) {
            'short_text', 'phone' => ['string', 'max:255'],
            'paragraph' => ['string', 'max:10000'],
            'email' => ['string', 'email', 'max:255'],
            'url' => ['string', 'url', 'max:2048'],
            'number' => ['numeric'],
            'date' => ['date'],
            'time' => ['date_format:H:i'],
            'multiple_choice', 'dropdown' => $this->choiceRules($field),
            'checkboxes' => ['array'],
            'linear_scale' => ['integer', 'between:1,5'],
            'file_upload' => $this->fileRules($validation),
            default => ['string'],
        };

        if (in_array($field->type, ['short_text', 'paragraph', 'phone', 'number'], true)) {
            $rules = [...$rules, ...$this->numericRangeRules($validation)];
        }

        return $rules;
    }

    /**
     * @return list<mixed>
     */
    private function choiceRules(FormField $field): array
    {
        $rules = ['string'];

        if ($field->options->isNotEmpty()) {
            $rules[] = Rule::in($field->options->pluck('value')->all());
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $validation
     * @return list<mixed>
     */
    private function fileRules(array $validation): array
    {
        $rules = ['file'];

        $mimeTypes = $validation['mime_types'] ?? null;

        if (is_array($mimeTypes)) {
            $mimeTypes = array_values(array_filter($mimeTypes, 'is_string'));

            if ($mimeTypes !== []) {
                $rules[] = 'mimetypes:'.implode(',', $mimeTypes);
            }
        }

        $maxSizeKb = $validation['max_size_kb'] ?? null;

        if (is_numeric($maxSizeKb) && (int) $maxSizeKb > 0) {
            $rules[] = 'max:'.(int) $maxSizeKb;
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $validation
     * @return list<string>
     */
    private function numericRangeRules(array $validation): array
    {
        $rules = [];

        if (isset($validation['min']) && is_numeric($validation['min'])) {
            $rules[] = 'min:'.(int) $validation['min'];
        }

        if (isset($validation['max']) && is_numeric($validation['max'])) {
            $rules[] = 'max:'.(int) $validation['max'];
        }

        return $rules;
    }

    /**
     * Get custom validation messages written for applicants.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $form = $this->form();

        if ($form === null) {
            return [];
        }

        $messages = [
            'answers.required' => 'Please complete the form before submitting.',
            'answers.array' => 'Please complete the form before submitting.',
        ];

        foreach ($form->fields as $field) {
            $key = 'answers.'.$field->id;
            $label = $field->label;

            $messages[$key.'.required'] = "Please answer \"{$label}\".";
            $messages[$key.'.string'] = "Enter a valid answer for \"{$label}\".";
            $messages[$key.'.email'] = "Enter a valid email address for \"{$label}\".";
            $messages[$key.'.url'] = "Enter a valid URL for \"{$label}\".";
            $messages[$key.'.numeric'] = "Enter a number for \"{$label}\".";
            $messages[$key.'.integer'] = "Choose a value for \"{$label}\".";
            $messages[$key.'.between'] = "Choose a value between 1 and 5 for \"{$label}\".";
            $messages[$key.'.date'] = "Enter a valid date for \"{$label}\".";
            $messages[$key.'.date_format'] = "Enter a valid time for \"{$label}\".";
            $messages[$key.'.array'] = "Choose at least one option for \"{$label}\".";
            $messages[$key.'.in'] = "Choose a valid option for \"{$label}\".";
            $messages[$key.'.file'] = "Upload a file for \"{$label}\".";
            $messages[$key.'.mimetypes'] = "The file for \"{$label}\" must be a supported type.";
            $messages[$key.'.max'] = $field->type === 'file_upload'
                ? "The file for \"{$label}\" is too large."
                : "The answer for \"{$label}\" is too long.";
            $messages[$key.'.min'] = "The answer for \"{$label}\" is too short.";
            $messages[$key.'.*.string'] = "Choose a valid option for \"{$label}\".";
            $messages[$key.'.*.in'] = "Choose a valid option for \"{$label}\".";
        }

        return $messages;
    }

    private function form(): ?Form
    {
        if ($this->formResolved) {
            return $this->resolvedForm;
        }

        $this->formResolved = true;

        $token = $this->route('token');

        if (! is_string($token) || $token === '') {
            return null;
        }

        $form = Form::query()
            ->where('public_token', $token)
            ->with(['fields.options'])
            ->first();

        if ($form === null || ! $form->isAcceptingResponses()) {
            return null;
        }

        return $this->resolvedForm = $form;
    }
}
