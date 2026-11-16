<x-dashboard-layout :title="$jobPosting->title">
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-3">
            <a href="{{ route('job-postings.index') }}" class="text-sm text-muted underline underline-offset-4">← All job postings</a>

            <div class="flex flex-wrap items-start justify-between gap-6">
                <div class="flex min-w-0 flex-col gap-3">
                    <h1 class="text-3xl leading-tight font-medium tracking-tight wrap-anywhere sm:text-4xl">{{ $jobPosting->title }}</h1>
                    <div class="flex flex-wrap items-center gap-3">
                        <p class="text-sm text-muted">{{ collect([$jobPosting->department, $jobPosting->location])->filter()->join(' · ') ?: 'No department or location' }}</p>
                        <span class="rounded-full bg-canvas px-3 py-1.5 text-xs capitalize">{{ $jobPosting->status }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-3 sm:min-h-12">
                    @can('update', $jobPosting)
                        <button type="button" data-open-dialog="upload-candidates" aria-haspopup="dialog" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-brand px-5 text-sm font-medium text-white transition hover:bg-brand/90">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 16V4m0 0L8 8m4-4 4 4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Upload candidates
                        </button>
                    @endcan
                </div>
            </div>
        </div>

        <section class="flex flex-col gap-6 rounded-2xl border border-line/60 bg-paper p-6 sm:p-8">
            <dl class="grid gap-6 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5"><dt class="text-xs text-muted">Minimum experience</dt><dd class="text-sm font-medium">{{ $jobPosting->min_experience_years !== null ? $jobPosting->min_experience_years . ' years' : 'Not specified' }}</dd></div>
                <div class="flex flex-col gap-1.5"><dt class="text-xs text-muted">Applications</dt><dd class="text-sm font-medium">{{ $jobPosting->applications_count }}</dd></div>
            </dl>

            <div class="flex flex-col gap-2"><h2 class="text-sm font-medium">Description</h2><p class="text-sm leading-relaxed whitespace-pre-line text-muted">{{ $jobPosting->description }}</p></div>

            @if ($jobPosting->requirements)
                <div class="flex flex-col gap-2"><h2 class="text-sm font-medium">Requirements</h2><p class="text-sm leading-relaxed whitespace-pre-line text-muted">{{ $jobPosting->requirements }}</p></div>
            @endif

            <div class="flex flex-col gap-2">
                <h2 class="text-sm font-medium">Required skills</h2>
                <ul class="flex flex-wrap gap-2">@foreach ($jobPosting->required_skills as $skill)<li class="rounded-full bg-canvas px-3 py-1.5 text-xs">{{ $skill }}</li>@endforeach</ul>
            </div>

            @if (! empty($jobPosting->nice_to_have))
                <div class="flex flex-col gap-2">
                    <h2 class="text-sm font-medium">Nice to have</h2>
                    <ul class="flex flex-wrap gap-2">@foreach ($jobPosting->nice_to_have as $skill)<li class="rounded-full border border-line px-3 py-1.5 text-xs">{{ $skill }}</li>@endforeach</ul>
                </div>
            @endif
        </section>

        @can('update', $jobPosting)
            <div class="flex flex-wrap items-center justify-between gap-4">
                <form action="{{ route('job-postings.destroy', $jobPosting) }}" method="POST" onsubmit="return confirm('Delete this job posting?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex min-h-11 items-center text-sm text-red-800 underline underline-offset-4">Delete job posting</button>
                </form>
                <a href="{{ route('job-postings.edit', $jobPosting) }}" class="inline-flex min-h-11 items-center rounded-xl bg-brand px-6 text-sm font-medium text-white transition hover:bg-brand/90">Edit job posting</a>
            </div>
        @endcan
    </div>
    @can('update', $jobPosting)
        <dialog id="upload-candidates" aria-labelledby="upload-candidates-heading" @if ($errors->has('files') || $errors->has('files.*') || session('intake_rejected')) data-open-on-load @endif
            class="m-auto w-[calc(100%-2rem)] max-w-xl rounded-2xl border border-line bg-paper p-0 text-ink backdrop:bg-ink/40">
            <div class="flex flex-col gap-5 p-6 sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex flex-col gap-1.5">
                        <h2 id="upload-candidates-heading" class="text-lg font-medium">Upload candidates</h2>
                        <p class="text-sm text-muted">Upload one PDF, several PDFs, or a ZIP of PDFs. Each PDF becomes one candidate for this job. Duplicates are skipped automatically.</p>
                    </div>
                    <button type="button" data-close-dialog aria-label="Close" class="flex size-11 shrink-0 items-center justify-center rounded-full border border-line text-lg">×</button>
                </div>

                @if (session('intake_rejected'))
                    <div role="alert" class="rounded-xl border border-red-700/40 px-5 py-4 text-sm text-red-800">
                        <p class="font-medium">{{ count(session('intake_rejected')) }} file(s) could not be imported:</p>
                        <ul class="mt-2 list-disc pl-5">@foreach (session('intake_rejected') as $reason)<li class="wrap-anywhere">{{ $reason }}</li>@endforeach</ul>
                    </div>
                @endif

                <form action="{{ route('job-postings.intake.pdf', $jobPosting) }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
                    @csrf
                    <label for="intake-files" @class(['flex min-h-40 cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border border-dashed px-6 py-8 text-center transition hover:bg-zinc-50 has-[:focus-visible]:outline-2', 'border-red-700' => $errors->has('files') || $errors->has('files.*'), 'border-line' => ! ($errors->has('files') || $errors->has('files.*'))])>
                        <svg class="size-8 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 16V4m0 0L8 8m4-4 4 4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="text-sm font-medium">Choose PDF or ZIP files</span>
                        <span class="text-xs text-muted">Up to 50 files, 50 MB each</span>
                        <span id="intake-selected" class="text-xs text-muted" aria-live="polite"></span>
                        <input id="intake-files" type="file" name="files[]" accept=".pdf,.zip,application/pdf,application/zip" multiple required class="sr-only" onchange="document.getElementById('intake-selected').textContent = this.files.length + ' file(s) selected'">
                    </label>
                    @foreach ($errors->get('files') as $message)<p role="alert" class="text-sm text-red-800">{{ $message }}</p>@endforeach
                    @foreach ($errors->get('files.*') as $messages)@foreach ($messages as $message)<p role="alert" class="text-sm text-red-800">{{ $message }}</p>@endforeach @endforeach
                    <div class="flex justify-end gap-3">
                        <button type="button" data-close-dialog class="inline-flex min-h-11 items-center rounded-xl border border-line px-5 text-sm">Cancel</button>
                        <x-primary-button>Upload candidates</x-primary-button>
                    </div>
                </form>
            </div>
        </dialog>
    @endcan
</x-dashboard-layout>
