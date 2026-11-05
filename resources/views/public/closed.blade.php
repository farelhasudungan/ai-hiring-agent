<x-layout :title="$form->title">
    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 rounded-[32px] border border-line/60 bg-paper p-8 sm:p-10">
        <p class="text-xs tracking-widest text-muted uppercase">{{ $form->company?->name ?? 'Application' }}</p>
        <h1 class="text-3xl leading-tight font-medium tracking-tight wrap-anywhere">{{ $form->title }}</h1>
        <p class="text-sm leading-relaxed text-muted">This form is not accepting new responses right now. If you followed a link from the hiring team, please contact them for an updated link.</p>
    </div>
</x-layout>
