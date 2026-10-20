<?php

namespace App\Http\Controllers;

use App\Actions\StartApplicationScreening;
use App\Http\Requests\StoreJobApplicationRequest;
use App\Models\Candidate;
use App\Models\Job;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class JobApplicationController extends Controller
{
    public function create(Job $job): View
    {
        abort_unless($job->status === 'open', 404);

        return view('jobs.apply', ['job' => $job->load('company')]);
    }

    public function store(StoreJobApplicationRequest $request, Job $job, StartApplicationScreening $screening): RedirectResponse
    {
        $data = $request->validated();
        $storedPaths = [];

        try {
            $application = DB::transaction(function () use ($data, $job, &$storedPaths) {
                // Recheck under a lock: a form opened earlier may now be closed.
                $openJob = Job::query()->whereKey($job->id)->where('status', 'open')->lockForUpdate()->firstOrFail();

                // Do not overwrite an existing candidate based on an unverified public email.
                $candidate = Candidate::create([
                    'company_id' => $openJob->company_id,
                    'full_name' => $data['full_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'linkedin_url' => $data['linkedin_url'] ?? null,
                    'portfolio_url' => $data['portfolio_url'] ?? null,
                    'source' => 'form',
                ]);

                $application = $openJob->applications()->create([
                    'company_id' => $openJob->company_id,
                    'candidate_id' => $candidate->id,
                    'source' => 'form',
                    'status' => 'received',
                    'submitted_at' => now(),
                ]);

                $directory = "applications/{$openJob->company_id}/{$application->id}";
                $resume = $data['resume'];
                $resumeName = $resume->hashName();
                $resumePath = $directory.'/'.$resumeName;
                $storedPaths[] = $resumePath;

                if ($resume->storeAs($directory, $resumeName, ['disk' => 'local', 'visibility' => 'private']) === false) {
                    throw new RuntimeException('CV tidak dapat disimpan.');
                }

                $application->documents()->create([
                    'type' => 'resume',
                    'file_name' => Str::limit(basename($resume->getClientOriginalName()), 255, ''),
                    'file_path' => $resumePath,
                    'mime_type' => $resume->getMimeType(),
                    'size_bytes' => $resume->getSize(),
                    'parsing_status' => 'pending',
                ]);

                if (filled($data['cover_letter'] ?? null)) {
                    $coverLetter = $data['cover_letter'];
                    $coverPath = $directory.'/'.Str::uuid().'.txt';
                    $storedPaths[] = $coverPath;

                    if (! Storage::disk('local')->put($coverPath, $coverLetter, ['visibility' => 'private'])) {
                        throw new RuntimeException('Cover letter tidak dapat disimpan.');
                    }

                    $application->documents()->create([
                        'type' => 'cover_letter',
                        'file_name' => 'cover-letter.txt',
                        'file_path' => $coverPath,
                        'mime_type' => 'text/plain',
                        'size_bytes' => strlen($coverLetter),
                        'parsing_status' => 'success',
                        'parsed_text' => $coverLetter,
                    ]);
                }

                $application->activityLogs()->create([
                    'candidate_id' => $candidate->id,
                    'action' => 'created',
                    'description' => 'Lamaran dikirim melalui formulir publik.',
                    'meta' => [
                        'data_processing_consent' => true,
                        'ai_screening_consent' => true,
                        'ai_provider' => 'gemini',
                        'consent_version' => 'gemini-screening-v1',
                        'consented_at' => now()->toIso8601String(),
                    ],
                ]);

                return $application;
            });
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('local')->delete($storedPaths);
            }

            throw $exception;
        }

        // Outside upload rollback handling: committed files must survive queue failures.
        $screening->handle($application);

        return to_route('jobs.apply', $job)->with('application_submitted', true);
    }
}
