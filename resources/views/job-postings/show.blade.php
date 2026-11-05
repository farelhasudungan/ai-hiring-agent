<x-dashboard-layout :title="$jobPosting->title">
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-5">
            <a href="{{ route('job-postings.index') }}" class="inline-flex w-fit items-center gap-2 text-sm text-muted underline-offset-4 hover:underline"><span aria-hidden="true">←</span> All job postings</a>

            <div class="flex flex-wrap items-start justify-between gap-6">
                <div class="flex min-w-0 flex-col gap-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <x-job-posting-status :status="$jobPosting->status" />
                        <span class="text-xs text-muted">Created {{ $jobPosting->created_at?->format('M j, Y') }}</span>
                    </div>
                    <h1 class="text-3xl leading-tight font-medium tracking-tight wrap-anywhere sm:text-4xl xl:text-5xl">{{ $jobPosting->title }}</h1>
                    <p class="text-sm text-muted">
                        {{ $jobPosting->department ?? 'No department' }}
                        <span aria-hidden="true">·</span> {{ $jobPosting->location ?? 'No location' }}
                        @if ($jobPosting->min_experience_years !== null)
                            <span aria-hidden="true">·</span> {{ $jobPosting->min_experience_years }}+ years experience
                        @endif
                    </p>
                </div>
                @can('update', $jobPosting)
                    <a href="{{ route('job-postings.edit', $jobPosting) }}" class="inline-flex min-h-12 items-center gap-4 rounded-[20px] border border-line bg-paper px-5 py-3 text-sm font-medium transition hover:bg-canvas">Edit posting <span aria-hidden="true">↗</span></a>
                @endcan
            </div>
        </div>

        @if ($errors->has('job_posting'))
            <div role="alert" class="rounded-[20px] border border-red-200 bg-red-50 px-6 py-4 text-sm text-red-800">{{ $errors->first('job_posting') }}</div>
        @endif

        <div class="grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
            <div class="flex min-w-0 flex-col gap-6">
                <section class="flex flex-col gap-4 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="description-heading">
                    <h2 id="description-heading" class="text-sm font-medium">About the role</h2>
                    <p class="text-sm leading-relaxed whitespace-pre-line">{{ $jobPosting->description }}</p>
                </section>

                @if ($jobPosting->requirements)
                    <section class="flex flex-col gap-4 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="requirements-heading">
                        <h2 id="requirements-heading" class="text-sm font-medium">Requirements</h2>
                        <p class="text-sm leading-relaxed whitespace-pre-line">{{ $jobPosting->requirements }}</p>
                    </section>
                @endif

                <section class="flex flex-col gap-6 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="skills-heading">
                    <h2 id="skills-heading" class="text-sm font-medium">Skills</h2>

                    <div class="flex flex-col gap-3">
                        <p class="text-xs tracking-widest text-muted uppercase">Required</p>
                        @if (! empty($jobPosting->required_skills))
                            <div class="flex flex-wrap gap-2">
                                @foreach ($jobPosting->required_skills as $skill)
                                    <span class="rounded-full bg-ink px-3 py-1.5 text-xs text-paper">{{ $skill }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-muted">No required skills listed.</p>
                        @endif
                    </div>

                    <div class="flex flex-col gap-3 border-t border-line/60 pt-5">
                        <p class="text-xs tracking-widest text-muted uppercase">Nice to have</p>
                        @if (! empty($jobPosting->nice_to_have))
                            <div class="flex flex-wrap gap-2">
                                @foreach ($jobPosting->nice_to_have as $skill)
                                    <span class="rounded-full bg-canvas px-3 py-1.5 text-xs">{{ $skill }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-muted">No optional skills listed.</p>
                        @endif
                    </div>
                </section>
            </div>

            <div class="flex min-w-0 flex-col gap-6">
                <section class="flex flex-col gap-5 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="details-heading">
                    <h2 id="details-heading" class="text-sm font-medium">Details</h2>
                    <dl class="flex flex-col gap-4">
                        <div class="flex items-center justify-between gap-4"><dt class="text-xs text-muted">Applications</dt><dd class="text-sm font-medium">{{ $jobPosting->applications_count }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-xs text-muted">Department</dt><dd class="text-sm font-medium">{{ $jobPosting->department ?? '—' }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-xs text-muted">Location</dt><dd class="text-sm font-medium">{{ $jobPosting->location ?? '—' }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-xs text-muted">Experience</dt><dd class="text-sm font-medium">{{ $jobPosting->min_experience_years !== null ? $jobPosting->min_experience_years.'+ years' : '—' }}</dd></div>
                    </dl>
                </section>

                <section class="flex flex-col gap-4 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="form-heading">
                    <h2 id="form-heading" class="text-sm font-medium">Application form</h2>
                    @if ($jobPosting->form)
                        <div class="flex flex-col gap-3">
                            <p class="font-medium wrap-anywhere">{{ $jobPosting->form->title }}</p>
                            <div class="flex flex-wrap items-center gap-2 text-xs text-muted">
                                <span class="rounded-full bg-canvas px-3 py-1 capitalize">{{ $jobPosting->form->status }}</span>
                                <span>Version {{ $jobPosting->form->version }}</span>
                            </div>
                            @if ($jobPosting->form->description)
                                <p class="text-sm leading-relaxed text-muted">{{ $jobPosting->form->description }}</p>
                            @endif
                        </div>
                    @else
                        <p class="text-sm leading-relaxed text-muted">No form is attached. Candidates cannot submit a form application for this role yet.</p>
                    @endif
                </section>

                @can('update', $jobPosting)
                    <section class="flex flex-col gap-4 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="status-heading">
                        <h2 id="status-heading" class="text-sm font-medium">Status</h2>
                        <form action="{{ route('job-postings.update-status', $jobPosting) }}" method="POST" class="flex flex-col gap-3">
                            @csrf
                            @method('PATCH')
                            <label for="status" class="sr-only">Job posting status</label>
                            <select id="status" name="status" class="min-h-12 w-full rounded-[20px] border border-line bg-paper px-4 text-sm">
                                @foreach (['draft' => 'Draft', 'open' => 'Open', 'closed' => 'Closed'] as $value => $label)
                                    <option value="{{ $value }}" @selected($jobPosting->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <p role="alert" class="text-sm text-red-800">{{ $message }}</p>
                            @enderror
                            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-[20px] bg-ink px-5 py-2 text-sm font-medium text-canvas transition hover:bg-ink/85">Update status</button>
                        </form>
                    </section>

                    @can('delete', $jobPosting)
                        <form action="{{ route('job-postings.destroy', $jobPosting) }}" method="POST" onsubmit="return confirm('Delete this job posting? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-[20px] border border-red-200 px-5 py-2 text-sm text-red-800 transition hover:bg-red-50">Delete job posting</button>
                        </form>
                    @endcan
                @endcan
            </div>
        </div>

        <section class="flex flex-col gap-6 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="applications-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="applications-heading" class="text-sm font-medium">Recent applications</h2>
                <span class="text-xs text-muted">{{ $jobPosting->applications_count }} total</span>
            </div>

            @if ($recentApplications->isEmpty())
                <p class="text-sm leading-relaxed text-muted">No applications yet. Share this role to start receiving candidates.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-line/60 text-[11px] tracking-widest text-muted uppercase">
                            <tr>
                                <th scope="col" class="py-3 pr-6 font-semibold">Candidate</th>
                                <th scope="col" class="py-3 pr-6 font-semibold">Status</th>
                                <th scope="col" class="py-3 pr-6 font-semibold">Match score</th>
                                <th scope="col" class="py-3 font-semibold">Submitted</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line/60">
                            @foreach ($recentApplications as $application)
                                <tr>
                                    <td class="py-4 pr-6">
                                        <p class="font-medium wrap-anywhere">{{ $application->candidate->full_name }}</p>
                                        <p class="mt-1 text-xs text-muted wrap-anywhere">{{ $application->candidate->email }}</p>
                                    </td>
                                    <td class="py-4 pr-6">
                                        <span class="rounded-full bg-canvas px-3 py-1 text-xs capitalize">{{ $application->status }}</span>
                                    </td>
                                    <td class="py-4 pr-6">{{ $application->latest_match_score !== null ? $application->latest_match_score.'%' : '—' }}</td>
                                    <td class="py-4 whitespace-nowrap text-muted">{{ $application->submitted_at?->format('M j, Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-dashboard-layout>
