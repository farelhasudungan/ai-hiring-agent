@props(['user'])

<div class="flex min-h-full flex-col gap-10 p-6">
    <a href="{{ route('dashboard') }}" class="flex min-h-12 items-center gap-3 px-4" aria-label="Roku workspace">
        <span class="flex size-6 shrink-0 items-center justify-center rounded-xs bg-black text-[15px] leading-[15px] font-bold text-white" aria-hidden="true">R</span>
        <span class="text-sm leading-5 font-semibold tracking-[-0.35px]">Roku<span class="block text-xs font-normal tracking-normal text-muted">Your hiring workspace</span></span>
    </a>

    <nav aria-label="Workspace navigation" class="flex flex-col gap-2">
        <p class="px-4 pb-2 font-mono text-[11px] tracking-[0.1em] text-zinc-400 uppercase">Workspace</p>
        <a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif
            @class(['flex min-h-12 items-center gap-3 rounded-xl px-4 text-sm font-medium transition', 'bg-zinc-100 text-ink' => request()->routeIs('dashboard'), 'text-muted hover:bg-zinc-50 hover:text-ink' => ! request()->routeIs('dashboard')])>
            <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>
            Overview
        </a>
        @if ($user->company_id)
            <a href="{{ route('job-postings.index') }}" @if (request()->routeIs('job-postings.*')) aria-current="page" @endif
                @class(['flex min-h-12 items-center gap-3 rounded-xl px-4 text-sm font-medium transition', 'bg-zinc-100 text-ink' => request()->routeIs('job-postings.*'), 'text-muted hover:bg-zinc-50 hover:text-ink' => ! request()->routeIs('job-postings.*')])>
                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                Job postings
            </a>
            <a href="{{ route('settings.ai.edit') }}" @if (request()->routeIs('settings.ai.*')) aria-current="page" @endif
                @class(['flex min-h-12 items-center gap-3 rounded-xl px-4 text-sm font-medium transition', 'bg-zinc-100 text-ink' => request()->routeIs('settings.ai.*'), 'text-muted hover:bg-zinc-50 hover:text-ink' => ! request()->routeIs('settings.ai.*')])>
                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8L12 3zM18 15l.8 2.2L21 18l-2.2.8L18 21l-.8-2.2L15 18l2.2-.8L18 15z" stroke-linejoin="round"/></svg>
                AI settings
            </a>
        @endif
    </nav>

    <div class="mt-auto flex flex-col gap-6">
        <div class="flex flex-col gap-4 border-t border-line/60 pt-6">
            <div class="flex min-w-0 items-center gap-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-canvas text-sm font-medium" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium">{{ $user->name }}</p>
                    <p class="truncate text-xs text-muted">{{ $user->email }}</p>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="flex min-h-11 w-full items-center justify-between rounded-xl px-4 text-sm text-muted transition hover:bg-zinc-50 hover:text-ink">Log out <span aria-hidden="true">↗</span></button>
            </form>
        </div>
    </div>
</div>
