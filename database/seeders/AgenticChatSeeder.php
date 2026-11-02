<?php

namespace Database\Seeders;

use App\Models\CandidateScoringAgent;
use App\Models\HiringRecommendationAgent;
use App\Models\ResumeParserAgent;
use App\Models\User;
use Illuminate\Database\Seeder;

class AgenticChatSeeder extends Seeder
{
    /** Seed agentic chat demo data without duplicating the demo sessions. */
    public function run(): void
    {
        $user = User::query()->first() ?? User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create agent instances
        $resumeParser = ResumeParserAgent::query()->firstOrCreate(
            ['slug' => 'resume-parser-v1'],
            [
                'name' => 'Resume Parser v1',
                'description' => 'Extracts structured data from candidate resumes using NLP.',
            ],
        );

        $candidateScorer = CandidateScoringAgent::query()->firstOrCreate(
            ['slug' => 'candidate-scorer-v1'],
            [
                'name' => 'Candidate Scorer v1',
                'description' => 'Scores candidates based on job requirements match.',
            ],
        );

        $hiringRecommender = HiringRecommendationAgent::query()->firstOrCreate(
            ['slug' => 'hiring-recommender-v1'],
            [
                'name' => 'Hiring Recommender v1',
                'description' => 'Generates shortlists and hiring recommendations.',
            ],
        );

        // Session 1: Analyzing a Laravel Developer candidate
        $session1 = $user->chatSessions()->firstOrCreate([
            'title' => 'Analisis Kandidat: Laravel Developer',
        ]);

        if ($session1->wasRecentlyCreated) {
            $msg1 = $session1->messages()->create([
                'role' => 'user',
                'content' => 'Tolong analisis CV kandidat Budi Santoso untuk posisi Laravel Developer. CV sudah di-upload sebagai resume_budi_santoso.pdf.',
            ]);

            $msg2 = $session1->messages()->create([
                'role' => 'assistant',
                'content' => 'Baik, saya akan memproses CV kandidat Budi Santoso. Memulai parsing dokumen resume...',
            ]);

            $msg2->toolExecutionLogs()->create([
                'loggable_type' => 'resume_parser',
                'loggable_id' => $resumeParser->id,
                'tool_name' => 'parse_resume',
                'input' => ['file_name' => 'resume_budi_santoso.pdf', 'format' => 'pdf'],
                'result' => [
                    'name' => 'Budi Santoso',
                    'skills' => ['PHP', 'Laravel', 'MySQL', 'Redis', 'Docker', 'Git'],
                    'experience_years' => 4,
                    'education' => 'S1 Teknik Informatika, ITS',
                    'certifications' => ['AWS Cloud Practitioner'],
                ],
                'status' => 'success',
                'duration_ms' => 1250,
            ]);

            $msg3 = $session1->messages()->create([
                'role' => 'assistant',
                'content' => 'Resume berhasil diparsing. Kandidat memiliki 4 tahun pengalaman dengan skill utama: PHP, Laravel, MySQL. Sekarang menjalankan scoring terhadap persyaratan posisi...',
            ]);

            $msg3->toolExecutionLogs()->create([
                'loggable_type' => 'candidate_scoring',
                'loggable_id' => $candidateScorer->id,
                'tool_name' => 'score_candidate',
                'input' => [
                    'candidate_skills' => ['PHP', 'Laravel', 'MySQL', 'Redis', 'Docker', 'Git'],
                    'required_skills' => ['PHP', 'Laravel', 'MySQL', 'REST API', 'Unit Testing'],
                    'experience_years' => 4,
                    'min_experience' => 3,
                ],
                'result' => [
                    'overall_score' => 82,
                    'skill_match' => 0.8,
                    'experience_match' => 1.0,
                    'missing_skills' => ['REST API', 'Unit Testing'],
                    'recommendation' => 'shortlist',
                ],
                'status' => 'success',
                'duration_ms' => 890,
            ]);

            $msg4 = $session1->messages()->create([
                'role' => 'assistant',
                'content' => 'Scoring selesai. Kandidat Budi Santoso mendapat skor 82/100. Direkomendasikan untuk shortlist. Skill yang belum terpenuhi: REST API dan Unit Testing.',
            ]);

            $session1->messages()->create([
                'role' => 'user',
                'content' => 'Bagus, masukkan ke shortlist. Ada kandidat lain untuk posisi yang sama?',
            ]);
        }

        // Session 2: Generating shortlist recommendation
        $session2 = $user->chatSessions()->firstOrCreate([
            'title' => 'Shortlist Rekomendasi: Backend Engineer',
        ]);

        if ($session2->wasRecentlyCreated) {
            $msg5 = $session2->messages()->create([
                'role' => 'user',
                'content' => 'Buatkan shortlist rekomendasi untuk posisi Backend Engineer dari semua kandidat yang sudah di-screening.',
            ]);

            $msg6 = $session2->messages()->create([
                'role' => 'assistant',
                'content' => 'Memproses semua kandidat yang telah di-screening untuk posisi Backend Engineer. Membandingkan skor dan kualifikasi...',
            ]);

            $msg6->toolExecutionLogs()->create([
                'loggable_type' => 'candidate_scoring',
                'loggable_id' => $candidateScorer->id,
                'tool_name' => 'batch_compare',
                'input' => [
                    'job_title' => 'Backend Engineer',
                    'candidate_ids' => [1, 2, 3, 4, 5],
                ],
                'result' => [
                    'rankings' => [
                        ['candidate_id' => 3, 'score' => 92],
                        ['candidate_id' => 1, 'score' => 82],
                        ['candidate_id' => 5, 'score' => 78],
                        ['candidate_id' => 2, 'score' => 65],
                        ['candidate_id' => 4, 'score' => 55],
                    ],
                ],
                'status' => 'success',
                'duration_ms' => 2100,
            ]);

            $msg7 = $session2->messages()->create([
                'role' => 'assistant',
                'content' => 'Perbandingan selesai. Menghasilkan rekomendasi hiring berdasarkan ranking dan kebutuhan tim...',
            ]);

            $msg7->toolExecutionLogs()->create([
                'loggable_type' => 'hiring_recommendation',
                'loggable_id' => $hiringRecommender->id,
                'tool_name' => 'generate_shortlist',
                'input' => [
                    'job_title' => 'Backend Engineer',
                    'max_shortlist' => 3,
                    'rankings' => [
                        ['candidate_id' => 3, 'score' => 92],
                        ['candidate_id' => 1, 'score' => 82],
                        ['candidate_id' => 5, 'score' => 78],
                    ],
                ],
                'result' => [
                    'shortlisted' => [3, 1, 5],
                    'summary' => 'Top 3 kandidat direkomendasikan untuk interview. Kandidat #3 sangat kuat di semua kriteria.',
                    'missing_qualifications' => [
                        ['candidate_id' => 1, 'gaps' => ['REST API', 'Unit Testing']],
                        ['candidate_id' => 5, 'gaps' => ['Docker', 'CI/CD']],
                    ],
                ],
                'status' => 'success',
                'duration_ms' => 1560,
            ]);

            $msg8 = $session2->messages()->create([
                'role' => 'assistant',
                'content' => "Shortlist rekomendasi untuk Backend Engineer:\n\n1. Kandidat #3 (Skor: 92) — Sangat kuat di semua kriteria\n2. Kandidat #1 (Skor: 82) — Perlu peningkatan di REST API & Unit Testing\n3. Kandidat #5 (Skor: 78) — Perlu peningkatan di Docker & CI/CD\n\nDisarankan untuk menjadwalkan interview dengan ketiga kandidat ini.",
            ]);

            $session2->messages()->create([
                'role' => 'user',
                'content' => 'Terima kasih, jadwalkan interview untuk ketiga kandidat tersebut.',
            ]);

            // Also add a failed tool execution for variety
            $msg9 = $session2->messages()->create([
                'role' => 'assistant',
                'content' => 'Mencoba mengambil detail jadwal yang tersedia...',
            ]);

            $msg9->toolExecutionLogs()->create([
                'loggable_type' => 'resume_parser',
                'loggable_id' => $resumeParser->id,
                'tool_name' => 'fetch_calendar',
                'input' => ['date_range' => '2026-11-10 to 2026-11-14'],
                'result' => null,
                'status' => 'failed',
                'duration_ms' => 3200,
                'error_message' => 'Calendar API timeout: service unavailable.',
            ]);
        }
    }
}
