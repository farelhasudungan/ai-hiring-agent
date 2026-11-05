<x-dashboard-layout title="Forms">
    <div class="flex flex-col gap-8">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="flex min-w-0 flex-col gap-3">
                <p class="flex items-center gap-2 text-xs font-semibold tracking-widest text-muted uppercase"><span class="size-2 rounded-full bg-accent" aria-hidden="true"></span>Application forms</p>
                <h1 class="text-3xl leading-tight font-medium tracking-tight wrap-anywhere sm:text-4xl">Forms</h1>
                <p class="text-sm leading-relaxed text-muted sm:text-base">Build the forms candidates use to apply, then attach them to job postings.</p>
            </div>
            @can('create', App\Models\Form::class)
                <a href="{{ route('forms.create') }}" class="inline-flex min-h-12 items-center gap-4 rounded-[20px] bg-ink px-5 py-3 text-sm font-medium text-canvas transition hover:bg-ink/85">New form <span aria-hidden="true">↗</span></a>
            @endcan
        </div>

        <nav aria-label="Filter forms by status" class="flex flex-wrap gap-2">
            @foreach (['all' => 'All', 'draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed'] as $key => $label)
                @php($filter = $key === 'all' ? null : $key)
                <a href="{{ route('forms.index', $filter === null ? [] : ['status' => $filter]) }}"
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

        @if ($forms->isEmpty())
            <section class="flex flex-col items-start gap-5 rounded-[32px] border border-dashed border-line bg-paper p-8 sm:p-12" aria-labelledby="empty-heading">
                <span class="flex size-16 items-center justify-center rounded-[20px] bg-canvas text-3xl text-muted" aria-hidden="true">+</span>
                <div class="flex flex-col gap-2">
                    <h2 id="empty-heading" class="text-2xl font-medium tracking-tight">{{ $activeStatus ? 'No '.$activeStatus.' forms' : 'No forms yet' }}</h2>
                    <p class="max-w-lg text-sm leading-relaxed text-muted">{{ $activeStatus ? 'Try a different status filter, or create a new form to get started.' : 'Create an application form once, then reuse it across job postings.' }}</p>
                </div>
                @can('create', App\Models\Form::class)
                    <a href="{{ route('forms.create') }}" class="inline-flex min-h-11 items-center gap-3 text-sm font-medium underline underline-offset-4">Create a form <span aria-hidden="true">↗</span></a>
                @endcan
            </section>
        @else
            <div class="overflow-hidden rounded-[32px] border border-line/60 bg-paper">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-line/60 text-[11px] tracking-widest text-muted uppercase">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-semibold">Form</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Status</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Fields</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Responses</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Jobs</th>
                                <th scope="col" class="px-6 py-4 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line/60">
                            @foreach ($forms as $form)
                                <tr class="transition hover:bg-canvas/60">
                                    <td class="px-6 py-5">
                                        <a href="{{ route('forms.show', $form) }}" class="font-medium underline-offset-4 hover:underline">{{ $form->title }}</a>
                                        <p class="mt-1 line-clamp-1 max-w-md text-xs text-muted">{{ $form->description ?: 'No description' }}</p>
                                    </td>
                                    <td class="px-6 py-5"><x-form-status :status="$form->status" /></td>
                                    <td class="px-6 py-5">{{ $form->fields_count }}</td>
                                    <td class="px-6 py-5">{{ $form->submissions_count }}</td>
                                    <td class="px-6 py-5">{{ $form->job_postings_count }}</td>
                                    <td class="px-6 py-5">
                                        <div class="flex justify-end gap-4 whitespace-nowrap">
                                            <a href="{{ route('forms.show', $form) }}" class="text-sm font-medium underline-offset-4 hover:underline">View</a>
                                            @can('update', $form)
                                                <a href="{{ route('forms.builder', $form) }}" class="text-sm text-muted underline-offset-4 hover:underline">Builder</a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($forms->hasPages())
                <div>{{ $forms->links() }}</div>
            @endif
        @endif
    </div>
</x-dashboard-layout>
