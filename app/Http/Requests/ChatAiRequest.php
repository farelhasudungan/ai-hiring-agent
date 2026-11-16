<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ChatAiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:1000'],
            'session_id' => [
                'nullable',
                'integer',
                Rule::exists('chat_sessions', 'id')->where(function ($query) {
                    $query->where('user_id', $this->user()?->id);
                }),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prompt.required' => 'Prompt wajib diisi.',
            'prompt.string' => 'Prompt harus berupa teks.',
            'prompt.max' => 'Prompt tidak boleh lebih dari 1000 karakter.',
            'session_id.integer' => 'Session ID harus berupa bilangan bulat.',
            'session_id.exists' => 'Sesi obrolan tidak ditemukan atau bukan milik pengguna yang sedang login.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Validasi gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
