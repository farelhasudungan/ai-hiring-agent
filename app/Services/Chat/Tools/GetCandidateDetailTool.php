<?php

namespace App\Services\Chat\Tools;

use App\Models\AiScreening;
use App\Models\Application;
use App\Services\Ai\Data\AiTool;
use Illuminate\Support\Facades\Storage;

class GetCandidateDetailTool implements ChatTool
{
    public const RESUME_CHARACTERS = 6000;

    private const SUMMARY_CHARACTERS = 3000;

    public function definition(): AiTool
    {
        return new AiTool(
            'get_candidate_detail',
            'Read one candidate in depth: AI screening summary, strengths, gaps, screening history and resume text. Use the application_id from search_candidates.',
            [
                'type' => 'object',
                'properties' => [
                    'application_id' => ['type' => 'integer', 'description' => 'The application_id of the candidate.'],
                ],
                'required' => ['application_id'],
            ],
        );
    }

    public function label(): string
    {
        return 'Reading candidate profile…';
    }

    public function execute(ChatToolContext $context, array $arguments): ChatToolResult
    {
        $id = $arguments['application_id'] ?? null;

        if (is_string($id) && ctype_digit($id)) {
            $id = (int) $id;
        }

        if (! is_int($id)) {
            return ChatToolResult::error('application_id must be an integer.');
        }

        $application = Application::query()
            ->where('company_id', $context->company->id)
            ->when($context->lockedJobId !== null, fn ($query) => $query->where('job_id', $context->lockedJobId))
            ->with([
                'job:id,title',
                'documents' => fn ($documents) => $documents->where('parsing_status', 'success')->latest('id'),
                'aiScreenings' => fn ($screenings) => $screenings->where('status', 'success')->latest('id')->limit(3),
            ])
            ->find($id);

        if ($application === null) {
            return ChatToolResult::error("No candidate with application_id {$id} is available in this search.");
        }

        $latest = $application->aiScreenings->first();

        return new ChatToolResult([
            'application_id' => $application->id,
            'name' => $application->full_name ?? 'Unknown candidate',
            'job' => $application->job?->title,
            'status' => $application->status,
            'source' => $application->source,
            'submitted_at' => $application->submitted_at?->toDateString(),
            'ai_score' => $application->latest_match_score,
            'recommendation' => $latest?->recommendation,
            'experience_years' => $latest?->experience_years,
            'education_level' => $latest?->education_level,
            'matched_skills' => $latest?->matched_skills ?? [],
            'missing_skills' => $latest?->missing_skills ?? [],
            'risk_flags' => $latest?->risk_flags ?? [],
            'strengths' => $latest?->parsed_response['strengths'] ?? [],
            'gaps' => $latest?->parsed_response['gaps'] ?? [],
            'summary' => $latest === null ? null : $this->summary($latest),
            'screening_history' => $application->aiScreenings->map(fn (AiScreening $screening): array => [
                'score' => $screening->match_score,
                'recommendation' => $screening->recommendation,
                'at' => $screening->completed_at?->toDateString(),
            ])->all(),
            'resume_text' => mb_substr((string) $application->documents->first()?->parsed_text, 0, self::RESUME_CHARACTERS),
        ], [$application->id]);
    }

    private function summary(AiScreening $screening): ?string
    {
        $disk = Storage::disk('local');

        if ($screening->summary_path === null || ! $disk->exists($screening->summary_path)) {
            return null;
        }

        return mb_substr((string) $disk->get($screening->summary_path), 0, self::SUMMARY_CHARACTERS);
    }
}
