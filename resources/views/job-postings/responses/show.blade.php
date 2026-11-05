<x-dashboard-layout :title="'Response · '.$response->respondentName()">
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-5">
            <a href="{{ route('job-postings.responses.index', $jobPosting) }}" class="inline-flex w-fit items-center gap-2 text-sm text-muted underline-offset-4 hover:underline"><span aria-hidden="true">←</span> All responses</a>

            <div class="flex flex-wrap items-start justify-between gap-6">
                <div class="flex min-w-0 flex-col gap-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <x-form-response-status :status="$response->status" />
                        <span class="text-xs text-muted">Submitted {{ $response->submitted_at?->format('M j, Y g:i A') ?? '—' }}</span>
                    </div>
                    <h1 class="text-3xl leading-tight font-medium tracking-tight wrap-anywhere sm:text-4xl">{{ $response->respondentName() }}</h1>
                    <p class="text-sm text-muted">{{ $jobPosting->title }} <span aria-hidden="true">·</span> {{ $response->respondentEmail() ?? 'No email provided' }}</p>
                </div>

                @can('update', $jobPosting)
                    <form action="{{ route('job-postings.responses.update-status', [$jobPosting, $response]) }}" method="POST" class="flex flex-wrap items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <label for="response-status" class="sr-only">Response status</label>
                        <select id="response-status" name="status" class="min-h-11 rounded-[20px] border border-line bg-paper px-4 text-sm">
                            @foreach (['submitted' => 'Submitted', 'processed' => 'Processed', 'spam' => 'Spam', 'draft' => 'Draft'] as $value => $label)
                                <option value="{{ $value }}" @selected($response->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-[20px] bg-ink px-5 py-2 text-sm font-medium text-canvas transition hover:bg-ink/85">Update</button>
                    </form>
                @endcan
            </div>
        </div>

        @if ($errors->has('status'))
            <div role="alert" class="rounded-[20px] border border-red-200 bg-red-50 px-6 py-4 text-sm text-red-800">{{ $errors->first('status') }}</div>
        @endif

        <div class="grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
            <div class="flex min-w-0 flex-col gap-6">
                @forelse ($answersBySection as $sectionTitle => $answers)
                    <section class="flex flex-col gap-4 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8">
                        <h2 class="text-sm font-medium">{{ $sectionTitle }}</h2>
                        <dl class="flex flex-col divide-y divide-line/60">
                            @foreach ($answers as $answer)
                                @php
                                    $value = $answer->value;
                                    if (is_array($value)) {
                                        $display = collect($value)->map(fn ($item) => is_scalar($item) ? (string) $item : json_encode($item))->implode(', ');
                                    } elseif (is_bool($value)) {
                                        $display = $value ? 'Yes' : 'No';
                                    } else {
                                        $display = $value !== null && $value !== '' ? (string) $value : '—';
                                    }
                                @endphp
                                <div class="flex flex-col gap-1.5 py-4 first:pt-0 last:pb-0">
                                    <dt class="text-xs text-muted">{{ $answer->field?->label ?? 'Removed question' }}</dt>
                                    <dd class="text-sm leading-relaxed whitespace-pre-line">{{ $display === '' ? '—' : $display }}</dd>
                                    @if ($answer->files->isNotEmpty())
                                        <ul class="mt-1 flex flex-col gap-1">
                                            @foreach ($answer->files as $file)
                                                <li class="text-xs text-muted">
                                                    {{ $file->file_name }}
                                                    @if ($file->size_bytes)
                                                        · {{ number_format($file->size_bytes / 1024, 1) }} KB
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @empty
                    <section class="rounded-[32px] border border-dashed border-line bg-paper p-8">
                        <p class="text-sm text-muted">This response has no answers recorded.</p>
                    </section>
                @endforelse
            </div>

            <div class="flex min-w-0 flex-col gap-6">
                <section class="flex flex-col gap-5 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="candidate-heading">
                    <h2 id="candidate-heading" class="text-sm font-medium">Candidate</h2>
                    @if ($response->candidate)
                        <dl class="flex flex-col gap-4">
                            <div class="flex items-center justify-between gap-4"><dt class="text-xs text-muted">Name</dt><dd class="text-sm font-medium wrap-anywhere">{{ $response->candidate->full_name }}</dd></div>
                            <div class="flex items-center justify-between gap-4"><dt class="text-xs text-muted">Email</dt><dd class="text-sm font-medium wrap-anywhere">{{ $response->candidate->email }}</dd></div>
                            <div class="flex items-center justify-between gap-4"><dt class="text-xs text-muted">Phone</dt><dd class="text-sm font-medium">{{ $response->candidate->phone ?? '—' }}</dd></div>
                            <div class="flex items-center justify-between gap-4"><dt class="text-xs text-muted">Source</dt><dd class="text-sm font-medium capitalize">{{ $response->candidate->source }}</dd></div>
                        </dl>
                    @else
                        <p class="text-sm leading-relaxed text-muted">This response is not linked to a candidate because the form did not collect an email address. Add an email question mapped to <span class="font-medium text-ink">Candidate email</span> to create candidate records from future submissions.</p>
                        <dl class="flex flex-col gap-4 border-t border-line/60 pt-4">
                            <div class="flex items-center justify-between gap-4"><dt class="text-xs text-muted">Name from response</dt><dd class="text-sm font-medium wrap-anywhere">{{ $response->respondentName() }}</dd></div>
                            <div class="flex items-center justify-between gap-4"><dt class="text-xs text-muted">Email</dt><dd class="text-sm font-medium">Not provided</dd></div>
                        </dl>
                    @endif
                </section>

                @if ($response->application)
                    <section class="flex flex-col gap-5 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="application-heading">
                        <h2 id="application-heading" class="text-sm font-medium">Application</h2>
                        <dl class="flex flex-col gap-4">
                            <div class="flex items-center justify-between gap-4"><dt class="text-xs text-muted">Status</dt><dd class="text-sm font-medium capitalize">{{ $response->application->status }}</dd></div>
                            <div class="flex items-center justify-between gap-4"><dt class="text-xs text-muted">Match score</dt><dd class="text-sm font-medium">{{ $response->application->latest_match_score !== null ? $response->application->latest_match_score.'%' : '—' }}</dd></div>
                            <div class="flex items-center justify-between gap-4"><dt class="text-xs text-muted">Source</dt><dd class="text-sm font-medium capitalize">{{ $response->application->source }}</dd></div>
                        </dl>
                    </section>
                @endif

                @php($screening = $response->aiScreenings->sortByDesc('created_at')->first())

                @if ($screening)
                    <section class="flex flex-col gap-3 rounded-[32px] border border-line/60 bg-paper p-6 sm:p-8" aria-labelledby="screening-heading">
                        <h2 id="screening-heading" class="text-sm font-medium">Latest AI screening</h2>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-canvas px-3 py-1 text-xs capitalize">{{ $screening->recommendation ?? $screening->status }}</span>
                            @if ($screening->match_score !== null)
                                <span class="rounded-full bg-canvas px-3 py-1 text-xs">{{ $screening->match_score }}% match</span>
                            @endif
                        </div>
                        @if ($screening->summary)
                            <p class="text-sm leading-relaxed text-muted">{{ $screening->summary }}</p>
                        @endif
                    </section>
                @endif

                @can('delete', $jobPosting)
                    <form action="{{ route('job-postings.responses.destroy', [$jobPosting, $response]) }}" method="POST" onsubmit="return confirm('Delete this response? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-[20px] border border-red-200 px-5 py-2 text-sm text-red-800 transition hover:bg-red-50">Delete response</button>
                    </form>
                @endcan
            </div>
        </div>
    </div>
</x-dashboard-layout>
