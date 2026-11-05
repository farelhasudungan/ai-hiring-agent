<?php

namespace App\Http\Requests;

use App\Models\JobPosting;

class UpdateJobPostingRequest extends StoreJobPostingRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $jobPosting = $this->route('jobPosting');

        return $jobPosting instanceof JobPosting
            && ($this->user()?->can('update', $jobPosting) ?? false);
    }
}
