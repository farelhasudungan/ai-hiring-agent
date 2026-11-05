<x-dashboard-layout title="Job postings">
    <div class="flex flex-col gap-8">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="flex min-w-0 flex-col gap-3">
                <p class="flex items-center gap-2 text-xs font-semibold tracking-widest text-muted uppercase"><span class="size-2 rounded-full bg-accent" aria-hidden="true"></span>Hiring pipeline</p>
                <h1 class="text-3xl leading-tight font-medium tracking-tight wrap-anywhere sm:text-4xl">Job postings</h1>
                <p class="text-sm leading-relaxed text-muted sm:text-base">Publish roles, keep requirements current, and track who applies.</p>
            </div>
            @can('create', App\Models\JobPosting::class)
                <a href="{{ route('job-postings.create') }}" class="inline-flex min-h-12 items-center gap-4 rounded-[20px] bg-ink px-5 py-3 text-sm font-medium text-canvas transition hover:bg-ink/85">New job posting <span aria-hidden="true">↗</span></a>
            @endcan
        </div>

        <nav aria-label="Filter job postings by status" class="flex flex-wrap gap-2">
            @foreach (['all' => 'All', 'draft' => 'Draft', 'open' => 'Open', 'closed' => 'Closed'] as $key => $label)
                @php($filter = $key === 'all' ? null : $key)
                <a href="{{ route('job-postings.index', $filter === null ? [] : ['status' => $filter]) }}"
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

        @if ($jobPostings->isEmpty())
            <section class="flex flex-col items-start gap-5 rounded-[32px] border border-dashed border-line bg-paper p-8 sm:p-12" aria-labelledby="empty-heading">
                <span class="flex size-16 items-center justify-center rounded-[20px] bg-canvas text-3xl text-muted" aria-hidden="true">+</span>
                <div class="flex flex-col gap-2">
                    <h2 id="empty-heading" class="text-2xl font-medium tracking-tight">{{ $activeStatus ? 'No '.$activeStatus.' roles' : 'No job postings yet' }}</h2>
                    <p class="max-w-lg text-sm leading-relaxed text-muted">
                        {{ $activeStatus ? 'Try a different status filter, or create a new role to start building this pipeline.' : 'Create your first role to start collecting applications and screening candidates.' }}
                    </p>
                </div>
                @can('create', App\Models\JobPosting::class)
                    <a href="{{ route('job-postings.create') }}" class="inline-flex min-h-11 items-center gap-3 text-sm font-medium underline underline-offset-4">Create a job posting <span aria-hidden="true">↗</span></a>
                @endcan
            </section>
        @else
            <div class="-mx-4 sm:-mx-8 xl:-mx-12">
                <div class="overflow-x-auto border-y border-line/60 bg-paper">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-line/60 text-[11px] tracking-widest text-muted uppercase">
                            <tr>
                                <th scope="col" class="px-4 py-4 font-semibold sm:px-8 xl:px-12">Role</th>
                                <th scope="col" class="px-4 py-4 font-semibold sm:px-6">Status</th>
                                <th scope="col" class="px-4 py-4 font-semibold sm:px-6">Applications</th>
                                <th scope="col" class="px-4 py-4 font-semibold sm:px-6">Created</th>
                                <th scope="col" class="px-4 py-4 text-right font-semibold sm:px-8 xl:px-12">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line/60">
                            @foreach ($jobPostings as $jobPosting)
                                <tr class="transition hover:bg-canvas/60">
                                    <td class="px-4 py-5 sm:px-8 xl:px-12">
                                        <a href="{{ route('job-postings.show', $jobPosting) }}" class="font-medium underline-offset-4 hover:underline">{{ $jobPosting->title }}</a>
                                        <p class="mt-1 text-xs text-muted">
                                            {{ $jobPosting->department ?? 'No department' }}
                                            <span aria-hidden="true">·</span> {{ $jobPosting->location ?? 'No location' }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-5 sm:px-6"><x-job-posting-status :status="$jobPosting->status" /></td>
                                    <td class="px-4 py-5 sm:px-6">{{ $jobPosting->applications_count }}</td>
                                    <td class="px-4 py-5 whitespace-nowrap text-muted sm:px-6">{{ $jobPosting->created_at?->format('M j, Y') }}</td>
                                    <td class="px-4 py-5 sm:px-8 xl:px-12">
                                        <div class="flex justify-end gap-4 whitespace-nowrap">
                                            <a href="{{ route('job-postings.show', $jobPosting) }}" class="text-sm font-medium underline-offset-4 hover:underline">View</a>
                                            @can('update', $jobPosting)
                                                <a href="{{ route('job-postings.edit', $jobPosting) }}" class="text-sm text-muted underline-offset-4 hover:underline">Edit</a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($jobPostings->hasPages())
                <div>{{ $jobPostings->links() }}</div>
            @endif
        @endif
    </div>
</x-dashboard-layout>
