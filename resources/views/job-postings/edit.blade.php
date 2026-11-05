<x-dashboard-layout title="Edit job posting">
    <div class="mx-auto flex w-full max-w-4xl flex-col gap-8">
        <div class="flex flex-col gap-4">
            <a href="{{ route('job-postings.show', $jobPosting) }}" class="inline-flex w-fit items-center gap-2 text-sm text-muted underline-offset-4 hover:underline"><span aria-hidden="true">←</span> Back to posting</a>
            <div class="flex flex-col gap-3">
                <p class="flex items-center gap-2 text-xs font-semibold tracking-widest text-muted uppercase"><span class="size-2 rounded-full bg-accent" aria-hidden="true"></span>Edit role</p>
                <h1 class="text-3xl leading-tight font-medium tracking-tight wrap-anywhere sm:text-4xl">{{ $jobPosting->title }}</h1>
                <p class="max-w-xl text-sm leading-relaxed text-muted sm:text-base">Keep the role description, requirements, and application form up to date.</p>
            </div>
        </div>

        <section class="rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-label="Job posting details">
            @include('job-postings._form', ['jobPosting' => $jobPosting, 'forms' => $forms])
        </section>
    </div>
</x-dashboard-layout>
