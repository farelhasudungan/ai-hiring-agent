<?php

namespace Database\Seeders;

use App\Models\AiScreening;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = Company::factory()->create([
            'name' => 'Demo Company',
            'slug' => 'demo-company',
            'plan' => 'free',
        ]);

        User::factory()->create([
            'company_id' => $company->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'admin',
        ]);

        $job = JobPosting::factory()->for($company)->create([
            'title' => 'Pengembang Laravel Senior',
            'department' => 'Rekayasa Perangkat Lunak',
            'location' => 'Jakarta',
            'status' => 'open',
            'description' => 'Kami mencari Pengembang Laravel Senior untuk merancang, membangun, dan memelihara aplikasi web yang andal dan mudah dikembangkan. Anda akan bekerja sama dengan tim produk dan desain, melakukan tinjauan kode, serta membimbing pengembang yang lebih junior.',
            'requirements' => 'Berpengalaman minimal 5 tahun mengembangkan aplikasi dengan PHP dan Laravel. Memahami perancangan basis data relasional, pengujian otomatis, dan pembuatan REST API. Mampu berkomunikasi dengan baik dalam tim dan bekerja secara mandiri.',
            'required_skills' => ['PHP', 'Laravel', 'MySQL', 'REST API', 'Git'],
            'nice_to_have' => ['Docker', 'AWS', 'Redis'],
            'min_experience_years' => 5,
        ]);

        JobPosting::factory()->for($company)->create([
            'title' => 'Desainer UI/UX',
            'department' => 'Desain Produk',
            'location' => 'Bandung (Hibrida)',
            'status' => 'open',
            'description' => 'Kami membutuhkan Desainer UI/UX yang bertanggung jawab merancang pengalaman pengguna pada platform rekrutmen kami, mulai dari riset pengguna, wireframe, hingga prototipe interaktif yang siap diserahkan kepada tim pengembang.',
            'requirements' => 'Berpengalaman minimal 2 tahun sebagai desainer produk digital. Mahir menggunakan Figma dan memahami prinsip aksesibilitas serta sistem desain. Memiliki portofolio yang menunjukkan proses berpikir desain.',
            'required_skills' => ['Figma', 'Riset Pengguna', 'Wireframing', 'Prototyping', 'Sistem Desain'],
            'nice_to_have' => ['HTML', 'CSS', 'Tailwind CSS'],
            'min_experience_years' => 2,
        ]);

        $application = Application::factory()->for($company)->create([
            'job_id' => $job->id,
            'full_name' => 'Jane Candidate',
            'email' => 'jane@example.com',
            'source' => 'manual',
        ]);

        $document = ApplicationDocument::factory()->for($application)->create([
            'file_path' => 'applications/jane-candidate/resume.pdf',
            'parsing_status' => 'success',
            'parsed_text' => 'Jane is a senior Laravel developer with five years of experience.',
        ]);

        $screening = AiScreening::factory()->for($application)->create([
            'job_id' => $job->id,
            'document_id' => $document->id,
            'status' => 'success',
            'input_snapshot' => [
                'documents' => [
                    [
                        'id' => $document->id,
                        'type' => $document->type,
                        'file_name' => $document->file_name,
                        'parsed_text_length' => strlen((string) $document->parsed_text),
                    ],
                ],
                'job' => [
                    'id' => $job->id,
                    'required_skills' => $job->required_skills,
                    'nice_to_have' => $job->nice_to_have,
                    'min_experience_years' => $job->min_experience_years,
                ],
            ],
            'match_score' => 87,
            'recommendation' => 'strong_match',
            'candidate_skills' => ['PHP', 'Laravel', 'MySQL', 'Docker'],
            'matched_skills' => ['PHP', 'Laravel', 'MySQL'],
            'missing_skills' => ['AWS'],
            'experience_years' => 5,
            'education_level' => 'bachelor',
            'started_at' => now()->subSeconds(12),
            'completed_at' => now(),
            'duration_ms' => 12_000,
        ]);

        $screening->update(['summary_path' => $screening->summaryPathForStorage()]);

        Storage::disk('local')->put($screening->summary_path, <<<'MARKDOWN'
            ## Jane Candidate

            **Match score:** 87 · **Recommendation:** strong match

            Strong backend match with the required Laravel and database skills.

            - **Matched:** PHP, Laravel, MySQL
            - **Missing:** AWS
            MARKDOWN);
    }
}
