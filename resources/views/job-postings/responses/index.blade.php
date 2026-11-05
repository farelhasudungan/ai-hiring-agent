<x-dashboard-layout :title="'Responses · '.$jobPosting->title">
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-5">
            <a href="{{ route('job-postings.show', $jobPosting) }}" class="inline-flex w-fit items-center gap-2 text-sm text-muted underline-offset-4 hover:underline"><span aria-hidden="true">←</span> Back to job posting</a>

            <div class="flex flex-wrap items-end justify-between gap-6">
                <div class="flex min-w-0 flex-col gap-3">
                    <p class="flex items-center gap-2 text-xs font-semibold tracking-widest text-muted uppercase"><span class="size-2 rounded-full bg-accent" aria-hidden="true"></span>Form responses</p>
                    <h1 class="text-3xl leading-tight font-medium tracking-tight wrap-anywhere sm:text-4xl">{{ $jobPosting->title }}</h1>
                    <p class="text-sm leading-relaxed text-muted">
                        {{ $jobPosting->form?->title ?? 'No form attached' }}
                        <span aria-hidden="true">·</span> {{ $statusCounts['all'] }} response(s)
                    </p>
                </div>
                @if ($jobPosting->form)
                    <a href="{{ route('forms.show', $jobPosting->form) }}" class="inline-flex min-h-12 items-center gap-3 rounded-[20px] border border-line bg-paper px-5 py-3 text-sm font-medium transition hover:bg-canvas">View form</a>
                @endif
            </div>
        </div>

        <nav aria-label="Filter responses by status" class="flex flex-wrap gap-2">
            @foreach (['all' => 'All', 'submitted' => 'Submitted', 'processed' => 'Processed', 'spam' => 'Spam', 'draft' => 'Draft'] as $key => $label)
                @php($filter = $key === 'all' ? null : $key)
                <a href="{{ route('job-postings.responses.index', array_filter(['jobPosting' => $jobPosting, 'status' => $filter])) }}"
                    @if ($activeStatus === $filter) aria-current="page" @endif
                    @class([
                        'inline-flex min-h-11 items-center gap-3 rounded-full border px-4 text-sm transition',
                        'border-ink bg-ink text-paper' => $activeStatus === $filter,
                        'border-line bg-paper text-muted hover:bg-canvas hover:text-ink' => $activeStatus !== $filter,
                    ])>
                    {{ $label }}
                    <span @class([
                        'rounded-full px-2 py-0.5 text-xs',
                        'bg-paper/15 text-paper' => $activeStatus === $filter,
                        'bg-canvas text-muted' => $activeStatus !== $filter,
                    ])>{{ $statusCounts[$key] }}</span>
                </a>
            @endforeach
        </nav>

        @if ($responses->isEmpty())
            <section class="flex flex-col items-start gap-5 rounded-[32px] border border-dashed border-line bg-paper p-8 sm:p-12" aria-labelledby="empty-responses-heading">
                <span class="flex size-16 items-center justify-center rounded-[20px] bg-canvas text-3xl text-muted" aria-hidden="true">+</span>
                <div class="flex flex-col gap-2">
                    <h2 id="empty-responses-heading" class="text-2xl font-medium tracking-tight">{{ $activeStatus ? 'No '.$activeStatus.' responses' : 'No responses yet' }}</h2>
                    <p class="max-w-lg text-sm leading-relaxed text-muted">{{ $activeStatus ? 'Try a different status filter.' : 'When candidates submit the application form, their responses will appear here.' }}</p>
                </div>
            </section>
        @else
            <div class="overflow-hidden rounded-[32px] border border-line/60 bg-paper">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-line/60 text-[11px] tracking-widest text-muted uppercase">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-semibold">Candidate</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Status</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Answers</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Submitted</th>
                                <th scope="col" class="px-6 py-4 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line/60">
                            @foreach ($responses as $response)
                                <tr class="transition hover:bg-canvas/60">
                                    <td class="px-6 py-5">
                                        <p class="font-medium wrap-anywhere">{{ $response->respondentName() }}</p>
                                        <p class="mt-1 text-xs text-muted wrap-anywhere">{{ $response->respondentEmail() ?? 'No email provided' }}</p>
                                        @if (! $response->candidate)
                                            <span class="mt-2 inline-flex rounded-full bg-canvas px-2.5 py-0.5 text-[11px] text-muted">Not linked to a candidate</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-5"><x-form-response-status :status="$response->status" /></td>
                                    <td class="px-6 py-5">{{ $response->answers_count }}</td>
                                    <td class="px-6 py-5 whitespace-nowrap text-muted">{{ $response->submitted_at?->format('M j, Y') ?? '—' }}</td>
                                    <td class="px-6 py-5 text-right">
                                        <a href="{{ route('job-postings.responses.show', [$jobPosting, $response]) }}" class="text-sm font-medium underline-offset-4 hover:underline">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($responses->hasPages())
                <div>{{ $responses->links() }}</div>
            @endif
        @endif
    </div>
</x-dashboard-layout>
