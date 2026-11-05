<x-layout :title="$form->title">
    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8">
        <div class="flex flex-col gap-4 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8">
            <p class="text-xs tracking-widest text-muted uppercase">{{ $form->company?->name ?? 'Application' }}</p>
            <h1 class="text-3xl leading-tight font-medium tracking-tight wrap-anywhere sm:text-4xl">{{ $form->title }}</h1>
            @if ($form->description)
                <p class="max-w-2xl text-sm leading-relaxed text-muted">{{ $form->description }}</p>
            @endif
            <p class="text-sm font-medium">Choose the role you would like to apply for.</p>
            @if ($form->closes_at)
                <p class="text-xs text-muted">Applications close {{ $form->closes_at->format('M j, Y g:i A') }}.</p>
            @endif
        </div>

        <div class="flex flex-col gap-3">
            @foreach ($jobPostings as $jobPosting)
                <a href="{{ $form->publicUrl($jobPosting) }}" class="flex flex-wrap items-center justify-between gap-4 rounded-[24px] border border-line/60 bg-paper p-5 transition hover:border-ink/40 hover:bg-canvas sm:p-6">
                    <span class="flex min-w-0 flex-col gap-1">
                        <span class="text-lg font-medium wrap-anywhere">{{ $jobPosting->title }}</span>
                        <span class="text-xs text-muted">{{ collect([$jobPosting->department, $jobPosting->location])->filter()->implode(' · ') ?: 'No department or location listed' }}</span>
                    </span>
                    <span class="inline-flex min-h-11 items-center gap-2 rounded-[20px] border border-line px-4 text-sm font-medium">Apply <span aria-hidden="true">↗</span></span>
                </a>
            @endforeach
        </div>
    </div>
</x-layout>
