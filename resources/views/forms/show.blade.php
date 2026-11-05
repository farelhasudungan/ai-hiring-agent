@php
    $typeLabels = [
        'short_text' => 'Short text',
        'paragraph' => 'Paragraph',
        'multiple_choice' => 'Multiple choice',
        'checkboxes' => 'Checkboxes',
        'dropdown' => 'Dropdown',
        'file_upload' => 'File upload',
        'date' => 'Date',
        'time' => 'Time',
        'email' => 'Email',
        'number' => 'Number',
        'url' => 'URL',
        'phone' => 'Phone',
        'linear_scale' => 'Linear scale',
    ];
@endphp

<x-dashboard-layout :title="$form->title">
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-5">
            <a href="{{ route('forms.index') }}" class="inline-flex w-fit items-center gap-2 text-sm text-muted underline-offset-4 hover:underline"><span aria-hidden="true">←</span> All forms</a>

            <div class="flex flex-wrap items-start justify-between gap-6">
                <div class="flex min-w-0 flex-col gap-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <x-form-status :status="$form->status" />
                        <span class="text-xs text-muted">Version {{ $form->version }}</span>
                    </div>
                    <h1 class="text-3xl leading-tight font-medium tracking-tight wrap-anywhere sm:text-4xl xl:text-5xl">{{ $form->title }}</h1>
                    @if ($form->description)
                        <p class="max-w-2xl text-sm leading-relaxed text-muted">{{ $form->description }}</p>
                    @endif
                </div>
                @can('update', $form)
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('forms.edit', $form) }}" class="inline-flex min-h-12 items-center gap-3 rounded-[20px] border border-line bg-paper px-5 py-3 text-sm font-medium transition hover:bg-canvas">Edit details</a>
                        <a href="{{ route('forms.builder', $form) }}" class="inline-flex min-h-12 items-center gap-4 rounded-[20px] bg-ink px-5 py-3 text-sm font-medium text-canvas transition hover:bg-ink/85">Open builder <span aria-hidden="true">↗</span></a>
                    </div>
                @endcan
            </div>
        </div>

        <div class="grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
            <div class="flex min-w-0 flex-col gap-6">
                @forelse ($form->sections as $section)
                    <section class="flex flex-col gap-5 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="section-{{ $section->id }}">
                        <div class="flex flex-col gap-1">
                            <h2 id="section-{{ $section->id }}" class="text-lg font-medium tracking-tight">{{ $section->title }}</h2>
                            @if ($section->description)
                                <p class="text-sm text-muted">{{ $section->description }}</p>
                            @endif
                        </div>

                        @if ($section->fields->isEmpty())
                            <p class="text-sm text-muted">No questions in this section yet.</p>
                        @else
                            <ol class="flex flex-col gap-4">
                                @foreach ($section->fields as $field)
                                    <li class="flex flex-col gap-2 rounded-[20px] bg-canvas/60 p-4">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-sm font-medium">{{ $field->label }}@if ($field->is_required)<span class="text-accent" aria-hidden="true"> *</span>@endif</p>
                                            <span class="rounded-full bg-paper px-2.5 py-0.5 text-[11px] text-muted">{{ $typeLabels[$field->type] ?? $field->type }}</span>
                                            @if ($field->maps_to)
                                                <span class="rounded-full bg-paper px-2.5 py-0.5 text-[11px] text-muted">maps to {{ $field->maps_to }}</span>
                                            @endif
                                        </div>
                                        @if ($field->help_text)
                                            <p class="text-xs text-muted">{{ $field->help_text }}</p>
                                        @endif
                                        @if ($field->options->isNotEmpty())
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($field->options as $option)
                                                    <span class="rounded-full border border-line bg-paper px-2.5 py-1 text-xs">{{ $option->label }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </section>
                @empty
                    <section class="flex flex-col items-start gap-4 rounded-[32px] border border-dashed border-line bg-paper p-8" aria-labelledby="empty-builder-heading">
                        <h2 id="empty-builder-heading" class="text-xl font-medium tracking-tight">This form has no questions yet.</h2>
                        <p class="max-w-lg text-sm leading-relaxed text-muted">Open the builder to add sections and fields. Candidates cannot provide useful answers until the form has fields.</p>
                        @can('update', $form)
                            <a href="{{ route('forms.builder', $form) }}" class="inline-flex min-h-11 items-center gap-3 text-sm font-medium underline underline-offset-4">Open builder <span aria-hidden="true">↗</span></a>
                        @endcan
                    </section>
                @endforelse
            </div>

            <div class="flex min-w-0 flex-col gap-6">
                <section class="flex flex-col gap-5 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="details-heading">
                    <h2 id="details-heading" class="text-sm font-medium">Details</h2>
                    <dl class="flex flex-col gap-4">
                        <div class="flex items-center justify-between gap-4"><dt class="text-xs text-muted">Fields</dt><dd class="text-sm font-medium">{{ $form->fields_count }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-xs text-muted">Responses</dt><dd class="text-sm font-medium">{{ $form->submissions_count }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-xs text-muted">Attached jobs</dt><dd class="text-sm font-medium">{{ $form->job_postings_count }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-xs text-muted">Closes at</dt><dd class="text-sm font-medium">{{ $form->closes_at?->format('M j, Y g:i A') ?? '—' }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-xs text-muted">Created</dt><dd class="text-sm font-medium">{{ $form->created_at?->format('M j, Y') }}</dd></div>
                    </dl>
                </section>

                @if ($form->settings['confirmation_message'] ?? null)
                    <section class="flex flex-col gap-3 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="confirmation-heading">
                        <h2 id="confirmation-heading" class="text-sm font-medium">Confirmation message</h2>
                        <p class="text-sm leading-relaxed text-muted">{{ $form->settings['confirmation_message'] }}</p>
                    </section>
                @endif

                @can('update', $form)
                    <section class="flex flex-col gap-4 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="form-status-heading">
                        <h2 id="form-status-heading" class="text-sm font-medium">Status</h2>
                        <form action="{{ route('forms.update-status', $form) }}" method="POST" class="flex flex-col gap-3">
                            @csrf
                            @method('PATCH')
                            <label for="form-status" class="sr-only">Form status</label>
                            <select id="form-status" name="status" class="min-h-12 w-full rounded-[20px] border border-line bg-paper px-4 text-sm">
                                @foreach (['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed'] as $value => $label)
                                    <option value="{{ $value }}" @selected($form->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <p role="alert" class="text-sm text-red-800">{{ $message }}</p>
                            @enderror
                            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-[20px] bg-ink px-5 py-2 text-sm font-medium text-canvas transition hover:bg-ink/85">Update status</button>
                        </form>
                    </section>

                    @can('delete', $form)
                        <form action="{{ route('forms.destroy', $form) }}" method="POST" onsubmit="return confirm('Delete this form? {{ $form->submissions_count > 0 ? 'This will also permanently delete '.$form->submissions_count.' response(s).' : 'This cannot be undone.' }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-[20px] border border-red-200 px-5 py-2 text-sm text-red-800 transition hover:bg-red-50">Delete form</button>
                        </form>
                    @endcan
                @endcan
            </div>
        </div>

        <section class="flex flex-col gap-6 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="jobs-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="jobs-heading" class="text-sm font-medium">Job postings using this form</h2>
                <span class="text-xs text-muted">{{ $form->job_postings_count }} attached</span>
            </div>

            @if ($form->jobPostings->isEmpty())
                <p class="text-sm leading-relaxed text-muted">This form is not attached to a job posting yet. Attach it from a job posting's edit page.</p>
            @else
                <ul class="divide-y divide-line/60">
                    @foreach ($form->jobPostings as $jobPosting)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-4">
                            <div class="flex min-w-0 flex-col gap-1">
                                <a href="{{ route('job-postings.show', $jobPosting) }}" class="text-sm font-medium underline-offset-4 hover:underline">{{ $jobPosting->title }}</a>
                                <p class="text-xs text-muted">{{ $jobPosting->department ?? 'No department' }} · {{ $jobPosting->location ?? 'No location' }}</p>
                            </div>
                            <a href="{{ route('job-postings.responses.index', $jobPosting) }}" class="text-sm text-muted underline-offset-4 hover:underline">View responses</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-dashboard-layout>
