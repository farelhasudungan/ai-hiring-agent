<?php

namespace App\Services;

use App\Models\FormSubmission;
use App\Models\JobPosting;
use App\Models\User;
use DomainException;

class FormSubmissionService
{
    /**
     * Statuses a form response may hold.
     *
     * @var list<string>
     */
    public const STATUSES = ['draft', 'submitted', 'processed', 'spam'];

    public function updateStatus(User $user, JobPosting $jobPosting, FormSubmission $submission, string $status): FormSubmission
    {
        $this->ensureBelongsToJobPosting($user, $jobPosting, $submission);

        if (! in_array($status, self::STATUSES, true)) {
            throw new DomainException('The selected response status is invalid.');
        }

        $submission->update(['status' => $status]);

        return $submission;
    }

    public function delete(User $user, JobPosting $jobPosting, FormSubmission $submission): void
    {
        $this->ensureBelongsToJobPosting($user, $jobPosting, $submission);

        $submission->delete();
    }

    private function ensureBelongsToJobPosting(User $user, JobPosting $jobPosting, FormSubmission $submission): void
    {
        if ($user->company_id === null || $user->company_id !== $jobPosting->company_id) {
            throw new DomainException('This job posting does not belong to your company.');
        }

        if ($submission->job_posting_id !== $jobPosting->id) {
            throw new DomainException('This response does not belong to the selected job posting.');
        }
    }
}
