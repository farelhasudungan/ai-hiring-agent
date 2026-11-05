@php
    $confirmation = $form->settings['confirmation_message']
        ?? 'Thank you for applying. We will review your submission and contact you if there is a match.';
@endphp

<x-layout :title="'Application received'">
    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 rounded-[32px] border border-line/60 bg-paper p-8 sm:p-10">
        <span class="flex size-14 items-center justify-center rounded-full bg-ink text-canvas" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" class="size-7" stroke="currentColor" stroke-width="1.8">
                <path d="m5 12.5 4.2 4.2L19 7" />
            </svg>
        </span>

        <div class="flex flex-col gap-3">
            <p class="text-xs tracking-widest text-muted uppercase">{{ $form->company?->name ?? 'Application' }}</p>
            <h1 class="text-3xl leading-tight font-medium tracking-tight">Application received</h1>
            @if ($jobPosting)
                <p class="text-sm text-muted">Your application for <span class="font-medium text-ink">{{ $jobPosting->title }}</span> has been submitted.</p>
            @endif
            <p class="text-sm leading-relaxed text-muted">{{ $confirmation }}</p>
        </div>
    </div>
</x-layout>
