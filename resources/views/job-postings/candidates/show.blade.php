@php
    $name = $application->full_name ?? $application->documents->first()?->origin_name ?? $application->documents->first()?->file_name ?? 'Candidate #'.$application->id;
    $result = $screening?->parsed_response ?? [];
    $canScreen = in_array($application->status, \App\Models\Application::SCREENABLE_STATUSES, true)
        && $application->documents->contains(fn ($document) => $document->parsing_status === 'success' && $document->parsed_text !== null);
    $recommendationStyles = [
        'strong_match' => 'bg-green-50 text-green-800',
        'shortlist' => 'bg-green-50 text-green-800',
        'review' => 'bg-amber-50 text-amber-800',
        'reject' => 'bg-red-50 text-red-800',
    ];
@endphp

<x-dashboard-layout :title="$name">
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-3">
            <a href="{{ route('job-postings.show', $jobPosting) }}" class="text-sm text-muted underline underline-offset-4">← {{ $jobPosting->title }}</a>

            <div class="flex flex-wrap items-start justify-between gap-6">
                <div class="flex min-w-0 flex-col gap-3">
                    <h1 class="text-3xl leading-tight font-medium tracking-tight wrap-anywhere sm:text-4xl">{{ $name }}</h1>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="rounded-full bg-canvas px-3 py-1.5 text-xs capitalize">{{ $application->status }}</span>
                        @if ($screening?->recommendation)
                            <span class="rounded-full px-3 py-1.5 text-xs font-medium {{ $recommendationStyles[$screening->recommendation] ?? 'bg-canvas' }}">{{ str_replace('_', ' ', $screening->recommendation) }}</span>
                        @endif
                    </div>
                </div>

                @can('update', $jobPosting)
                    @if ($canScreen)
                        <form action="{{ route('job-postings.screening.store', $jobPosting) }}" method="POST">
                            @csrf
                            <input type="hidden" name="application_ids[]" value="{{ $application->id }}">
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-brand px-5 text-sm font-medium text-white transition hover:bg-brand/90">{{ $application->status === 'failed' ? 'Retry AI screening' : 'Run AI screening' }}</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>

        @if (session('status'))
            <p role="status" class="rounded-xl border border-line px-6 py-4 text-sm">{{ session('status') }}</p>
        @endif
        @error('screening')<p role="alert" class="rounded-xl border border-red-700 px-6 py-4 text-sm text-red-800">{{ $message }}</p>@enderror

        <section class="flex flex-col gap-6 rounded-2xl border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="contact-heading">
            <h2 id="contact-heading" class="text-lg font-medium">Candidate</h2>
            <dl class="grid gap-6 sm:grid-cols-3">
                <div class="flex flex-col gap-1.5"><dt class="text-xs text-muted">Email</dt><dd class="text-sm font-medium wrap-anywhere">{{ $application->email ?? 'Not extracted yet' }}</dd></div>
                <div class="flex flex-col gap-1.5"><dt class="text-xs text-muted">Phone</dt><dd class="text-sm font-medium">{{ $application->phone ?? 'Not extracted yet' }}</dd></div>
                <div class="flex flex-col gap-1.5"><dt class="text-xs text-muted">Submitted</dt><dd class="text-sm font-medium">{{ $application->submitted_at?->format('d M Y H:i') }}</dd></div>
            </dl>
            @if ($application->documents->isNotEmpty())
                <ul class="flex flex-wrap gap-2">
                    @foreach ($application->documents as $document)
                        <li class="rounded-full border border-line px-3 py-1.5 text-xs wrap-anywhere">{{ $document->origin_name ?? $document->file_name }} · {{ $document->parsing_status }}</li>
                    @endforeach
                </ul>
            @endif
        </section>

        @if ($screening === null)
            <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-muted">This candidate has not been screened by AI yet.</p>
        @elseif ($screening->status === 'success')
            <section class="flex flex-col gap-6 rounded-2xl border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="screening-heading">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h2 id="screening-heading" class="text-lg font-medium">AI screening</h2>
                    <p class="text-xs text-muted">{{ $screening->model_name }} · {{ $screening->completed_at?->format('d M Y H:i') }}</p>
                </div>

                <p class="text-5xl font-medium tracking-tight">{{ $screening->match_score }}<span class="text-lg text-muted">/100</span></p>

                <div class="flex flex-col gap-2"><h3 class="text-sm font-medium">Summary</h3><p class="text-sm leading-relaxed whitespace-pre-line text-muted">{{ $result['summary'] ?? '' }}</p></div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <h3 class="text-sm font-medium">Strengths</h3>
                        <ul class="list-disc pl-5 text-sm text-muted">@forelse ($result['strengths'] ?? [] as $item)<li>{{ $item }}</li>@empty<li class="list-none">None listed.</li>@endforelse</ul>
                    </div>
                    <div class="flex flex-col gap-2">
                        <h3 class="text-sm font-medium">Gaps</h3>
                        <ul class="list-disc pl-5 text-sm text-muted">@forelse ($result['gaps'] ?? [] as $item)<li>{{ $item }}</li>@empty<li class="list-none">None listed.</li>@endforelse</ul>
                    </div>
                </div>

                @foreach (['matched_skills' => ['Matched skills', 'bg-green-50 text-green-800'], 'missing_skills' => ['Missing skills', 'bg-amber-50 text-amber-800'], 'risk_flags' => ['Risk flags', 'bg-red-50 text-red-800']] as $key => [$label, $style])
                    @if (! empty($screening->{$key}))
                        <div class="flex flex-col gap-2">
                            <h3 class="text-sm font-medium">{{ $label }}</h3>
                            <ul class="flex flex-wrap gap-2">@foreach ($screening->{$key} as $item)<li class="rounded-full px-3 py-1.5 text-xs {{ $style }}">{{ $item }}</li>@endforeach</ul>
                        </div>
                    @endif
                @endforeach

                <p class="text-xs text-muted">AI output is a recommendation only. The hiring decision stays with you.</p>
            </section>
        @else
            <p role="alert" class="rounded-2xl border border-red-700 p-6 text-sm text-red-800">The last AI screening failed: {{ $screening->error_message ?? 'unknown error' }}</p>
        @endif

        @if ($screening?->summaryMarkdown())
            <details class="rounded-2xl border border-line/60 bg-paper p-6">
                <summary class="cursor-pointer text-sm font-medium">Raw AI output (Markdown)</summary>
                <pre class="mt-4 overflow-x-auto text-xs leading-relaxed whitespace-pre-wrap wrap-anywhere text-muted">{{ $screening->summaryMarkdown() }}</pre>
            </details>
        @endif
    </div>
</x-dashboard-layout>
