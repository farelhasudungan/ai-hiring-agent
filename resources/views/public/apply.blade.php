@php
    $unsectionedFields = $form->fields->whereNull('form_section_id');
    $storeUrl = route('public.apply.store', array_filter([
        'token' => $form->public_token,
        'job' => $jobPosting?->id,
    ]));
@endphp

<x-layout :title="$form->title">
    <div class="mx-auto flex w-full max-w-4xl flex-col gap-8">
        <div class="flex flex-col gap-5 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8">
            <div class="flex flex-col gap-3">
                <p class="text-xs tracking-widest text-muted uppercase">{{ $form->company?->name ?? 'Application' }}</p>

                @if ($jobPosting)
                    <div class="flex flex-wrap items-center gap-2 text-sm text-muted">
                        <span>Applying for</span>
                        <span class="font-medium text-ink">{{ $jobPosting->title }}</span>
                        @if ($jobPosting->department || $jobPosting->location)
                            <span aria-hidden="true">·</span>
                            <span>{{ collect([$jobPosting->department, $jobPosting->location])->filter()->implode(' · ') }}</span>
                        @endif
                    </div>
                @endif

                <h1 class="text-3xl leading-tight font-medium tracking-tight wrap-anywhere sm:text-4xl">{{ $form->title }}</h1>

                @if ($form->description)
                    <p class="max-w-2xl text-sm leading-relaxed text-muted">{{ $form->description }}</p>
                @endif

                <div class="flex flex-wrap items-center gap-3 text-xs text-muted">
                    @if ($form->closes_at)
                        <span>Applications close {{ $form->closes_at->format('M j, Y g:i A') }}</span>
                    @endif
                    @if ($jobPostings->count() > 1 && $jobPosting)
                        <a href="{{ $form->publicUrl() }}" class="font-medium text-ink underline underline-offset-4">Change role</a>
                    @endif
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-[20px] border border-red-200 bg-red-50 px-6 py-4 text-sm text-red-800">
                <p class="font-medium">Please check the highlighted questions and try again.</p>
                @if ($errors->has('answers'))
                    <p class="mt-1">{{ $errors->first('answers') }}</p>
                @endif
            </div>
        @endif

        @if ($form->fields->isEmpty())
            <section class="flex flex-col items-start gap-3 rounded-[32px] border border-dashed border-line bg-paper p-8">
                <h2 class="text-xl font-medium tracking-tight">This application form is being prepared.</h2>
                <p class="max-w-lg text-sm leading-relaxed text-muted">Please check back soon, or contact the hiring team if you were given this link directly.</p>
            </section>
        @else
            <form method="POST" action="{{ $storeUrl }}" enctype="multipart/form-data" class="flex flex-col gap-6" data-public-application>
                @csrf

                @foreach ($form->sections as $section)
                    <section class="flex flex-col gap-5 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="public-section-{{ $section->id }}">
                        <div class="flex flex-col gap-1">
                            <h2 id="public-section-{{ $section->id }}" class="text-lg font-medium tracking-tight">{{ $section->title }}</h2>
                            @if ($section->description)
                                <p class="text-sm text-muted">{{ $section->description }}</p>
                            @endif
                        </div>

                        <div class="flex flex-col gap-6">
                            @foreach ($section->fields as $field)
                                @include('public._field', ['field' => $field])
                            @endforeach
                        </div>
                    </section>
                @endforeach

                @if ($unsectionedFields->isNotEmpty())
                    <section class="flex flex-col gap-5 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8">
                        <h2 class="text-lg font-medium tracking-tight">Questions</h2>
                        <div class="flex flex-col gap-6">
                            @foreach ($unsectionedFields as $field)
                                @include('public._field', ['field' => $field])
                            @endforeach
                        </div>
                    </section>
                @endif

                <div class="flex flex-wrap items-center justify-between gap-4 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8">
                    <p class="max-w-md text-xs leading-relaxed text-muted">Your answers will be shared with the hiring team. Fields marked <span class="text-accent">*</span> are required.</p>
                    <button type="submit" class="inline-flex min-h-12 items-center gap-4 rounded-[20px] bg-ink px-6 py-3 text-sm font-medium text-canvas transition hover:bg-ink/85">Submit application <span aria-hidden="true">↗</span></button>
                </div>
            </form>
        @endif
    </div>
</x-layout>
