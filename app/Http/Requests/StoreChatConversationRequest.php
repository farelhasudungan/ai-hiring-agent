<?php

namespace App\Http\Requests;

use App\Models\ChatConversation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChatConversationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', ChatConversation::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['nullable', 'string', 'max:'.StoreChatMessageRequest::MAX_LENGTH],
            'job_id' => ['nullable', 'integer', Rule::exists('job_postings', 'id')->where('company_id', $this->user()?->company_id)],
        ];
    }
}
