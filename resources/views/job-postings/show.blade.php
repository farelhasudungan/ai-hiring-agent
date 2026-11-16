<x-dashboard-layout :title="$jobPosting->title">
    <div class="flex flex-col gap-8">
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div class="flex min-w-0 flex-col gap-3">
                <a href="{{ route('job-postings.index') }}" class="text-sm text-muted underline underline-offset-4">← All job postings</a>
                <h1 class="text-3xl leading-tight font-medium tracking-tight wrap-anywhere sm:text-4xl">{{ $jobPosting->title }}</h1>
                <p class="text-sm text-muted">{{ collect([$jobPosting->department, $jobPosting->location])->filter()->join(' · ') ?: 'No department or location' }}</p>
            </div>
            <span class="rounded-full bg-canvas px-3 py-1.5 text-xs capitalize">{{ $jobPosting->status }}</span>
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
</x-dashboard-layout>
