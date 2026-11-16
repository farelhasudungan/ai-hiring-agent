<x-dashboard-layout title="Overview">
    <div class="flex flex-col gap-8">
        <div class="flex min-w-0 flex-col gap-3">
            <p class="font-mono text-[11px] tracking-[0.1em] text-zinc-400 uppercase">{{ $company ? $company->name : 'Overview' }}</p>
            <h1 class="text-3xl leading-tight font-medium tracking-tight wrap-anywhere sm:text-4xl xl:text-5xl">Welcome, {{ $user->name }}.</h1>
        </div>

        @if ($company)
            <section class="grid gap-6 sm:grid-cols-2" aria-label="Hiring statistics">
                <a href="{{ route('job-postings.index') }}" class="flex flex-col gap-3 rounded-2xl border border-line/60 bg-paper p-6 transition hover:border-line sm:p-8">
                    <p class="text-sm text-muted">Job postings</p>
                    <p class="text-5xl font-medium tracking-tight">{{ number_format($jobPostingCount) }}</p>
                </a>
                <div class="flex flex-col gap-3 rounded-2xl border border-line/60 bg-paper p-6 sm:p-8">
                    <p class="text-sm text-muted">Applicants</p>
                    <p class="text-5xl font-medium tracking-tight">{{ number_format($applicationCount) }}</p>
                </div>
            </section>
        @else
            <section class="flex flex-col items-start gap-4 rounded-2xl border border-line/60 bg-paper p-6 sm:p-8">
                <p class="text-sm leading-relaxed text-muted">Set up your company to start tracking job postings and applicants.</p>
                <a href="{{ route('companies.create') }}" class="mt-2 inline-flex min-h-12 items-center gap-6 rounded-xl bg-brand px-5 py-3 text-sm font-medium text-white transition hover:bg-brand/90">Create your company <span aria-hidden="true">↗</span></a>
            </section>
        @endif
    </div>
</x-dashboard-layout>
