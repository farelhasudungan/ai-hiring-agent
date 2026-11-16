<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreScreeningRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('jobPosting')) ?? false;
    }

    /**
     * Without ids every candidate waiting for screening is processed.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'application_ids' => ['nullable', 'array', 'max:200'],
            'application_ids.*' => ['integer', 'distinct'],
        ];
    }
}
