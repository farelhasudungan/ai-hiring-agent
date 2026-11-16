<?php

namespace App\Http\Requests;

use App\Models\AiProviderSetting;
use App\Services\Ai\AiProviderManager;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAiModelsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', AiProviderSetting::class) ?? false;
    }

    /**
     * The masked placeholder means the user did not type a new key.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('api_key') === AiProviderSetting::KEY_MASK) {
            $this->merge(['api_key' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', Rule::in(app(AiProviderManager::class)->names())],
            'api_key' => ['nullable', 'string', 'max:500'],
        ];
    }
}
