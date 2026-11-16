<x-dashboard-layout title="New job posting">
    <section class="flex w-full flex-col gap-8 rounded-2xl bg-paper p-6 sm:p-10" aria-labelledby="job-posting-heading">
        <div class="flex flex-col gap-3">
            <p class="font-mono text-[11px] tracking-[0.1em] text-zinc-400 uppercase">Job postings</p>
            <h1 id="job-posting-heading" class="text-3xl leading-tight font-medium tracking-tight">Create a job posting</h1>
        </div>

        <form action="{{ route('job-postings.store') }}" method="POST" class="flex flex-col gap-8">
            @csrf
            @include('job-postings._form')
            <div class="flex flex-wrap items-center justify-between gap-4">
                <a href="{{ route('job-postings.index') }}" class="inline-flex min-h-11 items-center text-sm text-muted underline underline-offset-4">Cancel</a>
                <x-primary-button>Create job posting</x-primary-button>
            </div>
        </form>
    </section>
</x-dashboard-layout>
