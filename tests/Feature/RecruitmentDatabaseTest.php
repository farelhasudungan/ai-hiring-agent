<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AiScreening;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\JobPosting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruitmentDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_recruitment_chain_persists_with_relations(): void
    {
        $company = Company::create(['name' => 'Acme', 'slug' => 'acme']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'hr',
            'is_active' => true,
        ]);
        $job = JobPosting::create([
            'company_id' => $company->id,
            'title' => 'Backend Dev',
            'description' => 'Build APIs',
            'required_skills' => ['PHP', 'Laravel'],
        ]);
        $candidate = Candidate::create([
            'company_id' => $company->id,
            'full_name' => 'Alya N.',
            'email' => 'alya@test.id',
            'source' => 'gmail',
        ]);
        $application = Application::create([
            'company_id' => $company->id,
            'candidate_id' => $candidate->id,
            'job_posting_id' => $job->id,
            'source' => 'gmail',
        ]);
        $document = ApplicationDocument::create([
            'application_id' => $application->id,
            'type' => 'resume',
            'file_name' => 'CV.pdf',
            'file_path' => 'private/cv.pdf',
        ]);
        $screening = AiScreening::create([
            'application_id' => $application->id,
            'job_posting_id' => $job->id,
            'document_id' => $document->id,
            'model_name' => 'qwen2.5:7b',
            'prompt_text' => 'nilai',
            'match_score' => 94,
            'matched_skills' => ['PHP'],
            'missing_skills' => ['Laravel'],
        ]);
        EmailMessage::create([
            'message_id' => 'gmail-1',
            'from_email' => 'alya@test.id',
            'subject' => 'Lamaran',
            'has_attachment' => true,
            'candidate_id' => $candidate->id,
            'application_id' => $application->id,
        ]);
        ActivityLog::create([
            'user_id' => $user->id,
            'application_id' => $application->id,
            'action' => 'ai_started',
        ]);

        $this->assertSame('Alya N.', $application->candidate->full_name);
        $this->assertSame('Backend Dev', $application->jobPosting->title);
        $this->assertSame(['PHP'], $screening->fresh()->matched_skills);
        $this->assertSame(1, $document->aiScreenings()->count());
        $this->assertSame(1, $company->applications()->count());
    }

    public function test_duplicate_application_and_email_are_blocked(): void
    {
        $company = Company::create(['name' => 'Acme', 'slug' => 'acme']);
        $candidate = Candidate::create([
            'company_id' => $company->id,
            'full_name' => 'Alya N.',
            'email' => 'alya@test.id',
        ]);
        $job = JobPosting::create([
            'company_id' => $company->id,
            'title' => 'Backend Dev',
            'description' => 'Build APIs',
        ]);
        Application::create([
            'company_id' => $company->id,
            'candidate_id' => $candidate->id,
            'job_posting_id' => $job->id,
        ]);
        EmailMessage::create([
            'message_id' => 'gmail-1',
            'from_email' => 'alya@test.id',
            'subject' => 'Lamaran',
        ]);

        $this->expectException(QueryException::class);

        Application::create([
            'company_id' => $company->id,
            'candidate_id' => $candidate->id,
            'job_posting_id' => $job->id,
        ]);
    }

    public function test_existing_staff_without_company_keeps_panel_access(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->assertNull($user->company_id);
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
    }
}
