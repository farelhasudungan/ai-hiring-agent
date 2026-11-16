<?php

namespace App\Http\Requests;

use App\Models\JobPosting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobPostingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', JobPosting::class) ?? false;
    }

    /**
     * Turn comma or newline separated skill text into clean arrays.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'required_skills' => $this->splitSkills($this->input('required_skills')),
            'nice_to_have' => $this->splitSkills($this->input('nice_to_have')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(JobPosting::STATUSES)],
            'description' => ['required', 'string', 'max:20000'],
            'requirements' => ['nullable', 'string', 'max:20000'],
            'required_skills' => ['required', 'array', 'min:1', 'max:50'],
            'required_skills.*' => ['string', 'max:100'],
            'nice_to_have' => ['nullable', 'array', 'max:50'],
            'nice_to_have.*' => ['string', 'max:100'],
            'min_experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
        ];
    }

    /**
     * @return array<int, string>|mixed
     */
    private function splitSkills(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return collect(preg_split('/[,\n]+/', $value) ?: [])
            ->map(fn (string $skill): string => trim($skill))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
