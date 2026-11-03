<?php

namespace Database\Seeders;

use App\Models\AiScreening;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
            'title' => 'Senior Laravel Developer',
            'status' => 'open',
        ]);

        $candidate = Candidate::factory()->for($company)->create([
            'full_name' => 'Jane Candidate',
            'email' => 'jane@example.com',
            'source' => 'form',
        ]);

        $application = Application::factory()->for($company)->create([
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'source' => 'form',
        ]);

        $document = ApplicationDocument::factory()->for($application)->create([
            'file_path' => 'applications/jane-candidate/resume.pdf',
            'parsing_status' => 'success',
            'parsed_text' => 'Jane is a senior Laravel developer with five years of experience.',
        ]);

        AiScreening::factory()->for($application)->create([
            'job_id' => $job->id,
            'document_id' => $document->id,
            'status' => 'success',
            'match_score' => 87,
            'recommendation' => 'strong_match',
            'summary' => 'Strong backend match with the required Laravel and database skills.',
            'candidate_skills' => ['PHP', 'Laravel', 'MySQL', 'Docker'],
            'matched_skills' => ['PHP', 'Laravel', 'MySQL'],
            'missing_skills' => ['AWS'],
            'experience_years' => 5,
            'education_level' => 'bachelor',
            'started_at' => now()->subSeconds(12),
            'completed_at' => now(),
            'duration_ms' => 12_000,
        ]);
    }
}
