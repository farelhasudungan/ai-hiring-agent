<?php

namespace App\Services\Screening;

use App\Models\AiScreening;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Services\Ai\AiService;
use App\Services\Screening\Exceptions\InvalidScreeningResponseException;
use DomainException;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CandidateScreeningService
{
    public function __construct(
        private AiService $ai,
        private ScreeningPromptBuilder $prompts,
        private ScreeningResultParser $parser,
    ) {}

    /**
     * Sends the job requirements and the resume to the company's AI and stores
     * the outcome. The raw AI reply is always kept as Markdown, even when it
     * cannot be parsed.
     *
     * @throws DomainException when the company has no AI provider.
     * @throws InvalidScreeningResponseException when the reply lacks the core fields.
     * @throws RuntimeException when there is no parsed resume text.
     */
    public function screen(Application $application): AiScreening
    {
        $document = $application->documents()
            ->where('parsing_status', 'success')
            ->whereNotNull('parsed_text')
            ->latest('id')
            ->first();

        if (! $document instanceof ApplicationDocument) {
            throw new RuntimeException('The application has no parsed resume text.');
        }

        $startedAt = now();
        $userPrompt = $this->prompts->userPrompt($application, $document->parsed_text);

        $screening = AiScreening::create([
            'application_id' => $application->id,
            'job_id' => $application->job_id,
            'document_id' => $document->id,
            'model_name' => $application->company->aiProviderSetting?->model ?? 'unconfigured',
            'status' => 'running',
            'prompt_text' => $userPrompt,
            'started_at' => $startedAt,
        ]);

        $application->update(['status' => 'analyzing', 'latest_ai_status' => 'running']);

        try {
            $response = $this->ai->complete($application->company, $this->prompts->systemPrompt(), $userPrompt, 1500);

            $path = $screening->summaryPathForStorage();
            Storage::disk('local')->put($path, $response->text);
            $screening->update(['model_name' => $response->model, 'summary_path' => $path, 'raw_response' => $response->text]);

            $result = $this->parser->parse($response->text);
        } catch (Throwable $exception) {
            $screening->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
                'duration_ms' => (int) $startedAt->diffInMilliseconds(now()),
            ]);

            throw $exception;
        }

        $screening->update([
            'status' => 'success',
            'match_score' => $result->score,
            'recommendation' => $result->recommendation,
            'parsed_response' => $result->payload,
            'matched_skills' => $result->matchedSkills,
            'missing_skills' => $result->missingSkills,
            'risk_flags' => $result->riskFlags,
            'completed_at' => now(),
            'duration_ms' => (int) $startedAt->diffInMilliseconds(now()),
        ]);

        $application->update([
            'full_name' => $application->full_name ?: $result->candidate['full_name'],
            'email' => $application->email ?: $result->candidate['email'],
            'phone' => $application->phone ?: $result->candidate['phone'],
            'status' => 'scored',
            'latest_match_score' => $result->score,
            'latest_ai_status' => 'success',
            'latest_ai_at' => now(),
        ]);

        return $screening;
    }
}
