<?php

namespace App\Jobs;

use App\Actions\Screening\AnalyzeCv;
use App\Actions\Screening\CalculateMatchScore;
use App\Actions\Screening\GatewayRequestException;
use App\Actions\Screening\ScreeningRun;
use App\Models\Application;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;
use UnexpectedValueException;

class ScoreApplicationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    public int $maxExceptions = 3;

    public int $timeout = 180;

    public bool $failOnTimeout = true;

    public function __construct(public int $applicationId, public string $runId) {}

    public function backoff(): array
    {
        return [30, 120];
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping("screening:{$this->applicationId}:{$this->runId}"))->shared()->releaseAfter(30)->expireAfter(210)];
    }

    public function handle(AnalyzeCv $analyze, CalculateMatchScore $calculate, ScreeningRun $run): void
    {
        try {
            $prepared = $run->locked($this->applicationId, $this->runId, function (Application $application) use ($analyze): ?array {
                $screening = $application->currentScreening()->where('application_id', $application->id)->firstOrFail();
                if ($screening->status === 'success') {
                    return null;
                }
                if (($screening->input_snapshot['ai_provider'] ?? null) !== config('ai.provider')) {
                    throw new UnexpectedValueException('Provider AI telah berubah. Proses ulang lamaran setelah memastikan persetujuan kandidat untuk Google Gemini.');
                }
                $document = $application->documents()->whereKey($screening->document_id)->where('type', 'resume')->firstOrFail();
                if ($document->parsing_status !== 'success' || blank($document->parsed_text)) {
                    throw new UnexpectedValueException('CV belum berhasil diekstrak; penilaian tidak dapat dilanjutkan.');
                }
                $prompt = $analyze->prompt($document->parsed_text, $screening->input_snapshot);
                $screening->update(['status' => 'running', 'prompt_text' => $analyze->systemPrompt()."\n\n".$prompt,
                    'started_at' => $screening->started_at ?? now(), 'error_message' => null]);
                $application->forceFill(['status' => 'analyzing', 'latest_ai_status' => 'running'])->save();

                return [$screening, $document->parsed_text, $prompt];
            });
            if (! $prepared) {
                return;
            }
            [$screening, $text, $prompt] = $prepared;
            $response = $analyze->handle($prompt, $text, $screening->model_name);
            $data = $response['data'];
            $result = $calculate->handle($data, $screening->input_snapshot);

            $run->locked($this->applicationId, $this->runId, function (Application $application) use ($screening, $response, $data, $result): void {
                $application->currentScreening()->where('application_id', $application->id)->firstOrFail()->update([
                    ...$result, 'status' => 'success', 'raw_response' => $response['content'], 'parsed_response' => $data,
                    'summary' => $data['summary'], 'experience_years' => $data['experience_years'],
                    'education_level' => $data['education_level'], 'risk_flags' => $data['risk_flags'],
                    'completed_at' => now(), 'duration_ms' => (int) $screening->started_at->diffInMilliseconds(now()),
                    'error_message' => null,
                ]);
                $application->forceFill([
                    'status' => 'scored', 'latest_ai_status' => 'success', 'latest_ai_at' => now(),
                    'latest_match_score' => $result['match_score'], 'processing_error' => null,
                ])->save();
            });
        } catch (UnexpectedValueException $exception) {
            $run->fail($this->applicationId, $this->runId, $exception->getMessage());
            $this->fail($exception);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $message = $exception instanceof GatewayRequestException
            ? $exception->getMessage().' Analisis gagal setelah percobaan ulang.'
            : 'Analisis AI gagal setelah percobaan ulang atau melewati batas waktu.';

        app(ScreeningRun::class)->fail($this->applicationId, $this->runId, $message);
    }
}
