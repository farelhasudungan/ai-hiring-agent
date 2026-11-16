<x-dashboard-layout title="Job postings">
    <div class="flex flex-col gap-8">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="flex flex-col gap-3">
                <p class="font-mono text-[11px] tracking-[0.1em] text-zinc-400 uppercase">Hiring</p>
                <h1 class="text-3xl leading-tight font-medium tracking-tight sm:text-4xl">Job postings</h1>
            </div>
            @can('create', \App\Models\JobPosting::class)
                <a href="{{ route('job-postings.create') }}" class="inline-flex min-h-11 items-center rounded-xl bg-brand px-6 text-sm font-medium text-white transition hover:bg-brand/90">New job posting</a>
            @endcan
        </div>

        <nav aria-label="Filter by status" class="flex flex-wrap gap-2 text-sm">
            @foreach ([null => 'All', 'draft' => 'Draft', 'open' => 'Open', 'closed' => 'Closed'] as $value => $label)
                <a href="{{ route('job-postings.index', $value ? ['status' => $value] : []) }}" @if (request('status') == $value) aria-current="page" @endif
                    @class(['inline-flex min-h-11 items-center rounded-full border px-4', 'border-ink bg-ink text-paper' => request('status') == $value, 'border-line text-muted' => request('status') != $value])>{{ $label }}</a>
            @endforeach
        </nav>

        <div class="flex flex-col gap-3">
        @forelse ($jobPostings as $jobPosting)
            <a href="{{ route('job-postings.show', $jobPosting) }}" class="flex flex-col gap-3 rounded-2xl border border-line/60 bg-paper p-6 transition hover:border-line">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-medium tracking-tight wrap-anywhere">{{ $jobPosting->title }}</h2>
                    <span class="rounded-full bg-canvas px-3 py-1.5 text-xs capitalize">{{ $jobPosting->status }}</span>
                </div>
                <p class="text-sm text-muted">{{ collect([$jobPosting->department, $jobPosting->location])->filter()->join(' · ') ?: 'No department or location' }} · {{ $jobPosting->applications_count }} {{ \Illuminate\Support\Str::plural('application', $jobPosting->applications_count) }}</p>
            </a>
        @empty
            <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-muted">No job postings yet.</p>
        @endforelse
        </div>

        {{ $jobPostings->links() }}
    </div>
</x-dashboard-layout>
