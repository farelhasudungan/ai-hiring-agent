<?php

namespace App\Jobs;

use App\Models\Application;
use App\Services\Screening\CandidateScreeningService;
use DomainException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ScreenApplication implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function __construct(public Application $application) {}

    public function handle(CandidateScreeningService $screening): void
    {
        try {
            $screening->screen($this->application);
        } catch (DomainException $exception) {
            $this->failed($exception);
            $this->fail($exception);
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->application->update(['status' => 'failed', 'latest_ai_status' => 'failed']);
    }
}
