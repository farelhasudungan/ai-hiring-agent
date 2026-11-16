<x-dashboard-layout title="Edit job posting">
    <section class="flex w-full flex-col gap-8 rounded-2xl bg-paper p-6 sm:p-10" aria-labelledby="job-posting-heading">
        <div class="flex flex-col gap-3">
            <p class="font-mono text-[11px] tracking-[0.1em] text-zinc-400 uppercase">Job postings</p>
            <h1 id="job-posting-heading" class="text-3xl leading-tight font-medium tracking-tight wrap-anywhere">Edit {{ $jobPosting->title }}</h1>
        </div>

        <form action="{{ route('job-postings.update', $jobPosting) }}" method="POST" class="flex flex-col gap-8">
            @csrf
            @method('PUT')
            @include('job-postings._form', ['jobPosting' => $jobPosting])
            <div class="flex flex-wrap items-center justify-between gap-4">
                <a href="{{ route('job-postings.show', $jobPosting) }}" class="inline-flex min-h-11 items-center text-sm text-muted underline underline-offset-4">Cancel</a>
                <x-primary-button>Save changes</x-primary-button>
            </div>
        </form>
    </section>
</x-dashboard-layout>
