@props(['conversations', 'current' => null])

<div class="flex min-h-0 flex-1 flex-col" data-chat-tabs>
    <div class="flex items-center justify-between gap-3 border-b border-line/60 bg-paper px-4">
        <div role="tablist" aria-label="Candidate chat" class="flex gap-1">
            @foreach (['chat' => 'Chat', 'history' => 'History'] as $key => $label)
                <button type="button" role="tab" id="chat-tab-{{ $key }}" data-chat-tab="{{ $key }}" aria-controls="chat-panel-{{ $key }}" aria-selected="{{ $key === 'chat' ? 'true' : 'false' }}" tabindex="{{ $key === 'chat' ? '0' : '-1' }}"
                    class="relative min-h-12 px-4 text-sm font-medium text-muted transition hover:text-ink aria-selected:text-ink aria-selected:after:absolute aria-selected:after:inset-x-3 aria-selected:after:bottom-0 aria-selected:after:h-0.5 aria-selected:after:rounded-full aria-selected:after:bg-ink">{{ $label }}@if ($key === 'history') <span class="ml-1 rounded-full bg-canvas px-2 py-0.5 text-xs font-normal">{{ $conversations->count() }}</span>@endif</button>
            @endforeach
        </div>
        <a href="{{ route('chat.index') }}" class="inline-flex min-h-9 items-center gap-2 rounded-full border border-line px-4 text-sm font-medium transition hover:bg-zinc-50">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
            New search
        </a>
    </div>

    <div role="tabpanel" id="chat-panel-chat" aria-labelledby="chat-tab-chat" class="flex min-h-0 flex-1 flex-col">
        {{ $slot }}
    </div>

    <div role="tabpanel" id="chat-panel-history" aria-labelledby="chat-tab-history" hidden class="min-h-0 flex-1 overflow-y-auto">
        <nav class="mx-auto flex w-full max-w-3xl flex-col gap-2 px-4 py-8" aria-label="Search history">
            @forelse ($conversations as $item)
                <a href="{{ route('chat.show', $item) }}" @if ($current?->is($item)) aria-current="page" @endif
                    @class(['flex flex-wrap items-center justify-between gap-2 rounded-2xl border bg-paper p-4 transition hover:border-zinc-400', 'border-ink' => $current?->is($item), 'border-line/60' => ! $current?->is($item)])>
                    <span class="min-w-0 truncate text-sm font-medium">{{ $item->title }}</span>
                    <span class="text-xs text-muted">{{ $item->updated_at->diffForHumans() }}</span>
                </a>
            @empty
                <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-muted">No searches yet.</p>
            @endforelse
        </nav>
    </div>
</div>
