<?php

namespace App\Http\Requests;

use App\Models\JobPosting;
use App\Services\JobPostingService;
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
            'status' => ['required', Rule::in(JobPostingService::STATUSES)],
            'description' => ['required', 'string'],
            'requirements' => ['nullable', 'string'],
            'required_skills' => ['nullable', 'string', 'max:2000'],
            'nice_to_have' => ['nullable', 'string', 'max:2000'],
            'min_experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'form_id' => [
                'nullable',
                'integer',
                Rule::exists('forms', 'id')->where('company_id', $this->user()?->company_id),
            ],
        ];
    }
}
