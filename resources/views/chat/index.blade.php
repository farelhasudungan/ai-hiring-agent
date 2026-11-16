<x-dashboard-layout title="Find candidates" flush>
    <x-chat-tabs :conversations="$conversations">
        <section class="flex min-h-0 min-w-0 flex-1 flex-col overflow-y-auto" aria-labelledby="chat-heading">
            <form action="{{ route('chat.store') }}" method="POST" class="mx-auto flex w-full max-w-3xl flex-1 flex-col justify-center gap-8 px-4 py-10">
                @csrf

                <div class="flex flex-col items-center gap-3 text-center">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-black text-lg font-bold text-white" aria-hidden="true">R</span>
                    <h1 id="chat-heading" class="text-3xl leading-tight font-medium tracking-tight">Who are you looking for?</h1>
                    <p class="max-w-md text-sm leading-relaxed text-muted">Ask in plain language. I search the resumes and AI screenings you already have.</p>
                </div>

                @unless ($hasAiProvider)
                    <p role="alert" class="rounded-xl border border-amber-700 bg-amber-50 px-4 py-3 text-sm text-amber-900">Set up an AI provider in <a href="{{ route('settings.ai.edit') }}" class="underline">AI settings</a> before using the chat.</p>
                @endunless

                <x-chat-composer :max-length="\App\Http\Requests\StoreChatMessageRequest::MAX_LENGTH" :disabled="! $hasAiProvider">{{ old('message') }}</x-chat-composer>

                <div class="flex flex-wrap items-center justify-center gap-2">
                    <label for="job_id" class="text-sm text-muted">Search in</label>
                    <select id="job_id" name="job_id" class="min-h-11 rounded-full border border-line bg-paper px-4 text-sm">
                        <option value="">All job postings</option>
                        @foreach ($jobPostings as $jobPosting)
                            <option value="{{ $jobPosting->id }}" @selected((int) old('job_id', $selectedJobId) === $jobPosting->id)>{{ $jobPosting->title }}</option>
                        @endforeach
                    </select>
                </div>
                @error('job_id')<p role="alert" class="text-center text-sm text-red-800">{{ $message }}</p>@enderror
                @error('message')<p role="alert" class="text-center text-sm text-red-800">{{ $message }}</p>@enderror

                <div class="flex flex-wrap justify-center gap-2" aria-label="Example questions">
                    @foreach (['Laravel developers with 3+ years of experience', 'Top 5 candidates by AI score', 'Who knows Docker and PostgreSQL?'] as $example)
                        <button type="button" data-chat-example="{{ $example }}" class="rounded-full border border-line bg-paper px-4 py-2 text-sm text-muted transition hover:border-zinc-400 hover:text-ink">{{ $example }}</button>
                    @endforeach
                </div>
            </form>
        </section>
    </x-chat-tabs>
</x-dashboard-layout>
