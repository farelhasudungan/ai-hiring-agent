<?php

namespace App\Http\Requests;

use App\Models\Job;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class StoreJobApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $job = $this->route('job');
        abort_unless($job instanceof Job && $job->status === 'open', 404);

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'linkedin_url' => ['nullable', 'string', 'url:http,https', 'max:255'],
            'portfolio_url' => ['nullable', 'string', 'url:http,https', 'max:255'],
            'resume' => ['required', File::types(['pdf', 'docx'])->max(5120), 'extensions:pdf,docx'],
            'cover_letter' => ['nullable', 'string', 'max:10000'],
            'consent' => ['required', 'accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan alamat email yang valid.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'resume.required' => 'CV wajib diunggah.',
            'resume.uploaded' => 'CV gagal diunggah. Periksa batas upload server dan coba lagi.',
            'resume.mimes' => 'CV harus berupa berkas PDF atau DOCX.',
            'resume.extensions' => 'Ekstensi CV harus PDF atau DOCX.',
            'resume.max' => 'Ukuran CV maksimal 5 MB.',
            'consent.required' => 'Persetujuan pemrosesan data wajib diberikan.',
            'consent.accepted' => 'Persetujuan pemrosesan data wajib diberikan.',
        ];
    }
}
