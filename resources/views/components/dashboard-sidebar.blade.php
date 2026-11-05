@props(['user'])

<div class="flex min-h-full flex-col gap-10 p-6">
    <a href="{{ route('dashboard') }}" class="flex min-h-12 items-center gap-3 font-medium tracking-tight">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-ink text-lg text-paper" aria-hidden="true">h.</span>
        <span>Hiring agent<span class="mt-0.5 block text-xs font-normal tracking-normal text-muted">Your hiring workspace</span></span>
    </a>

    <nav aria-label="Workspace navigation" class="flex flex-col gap-2">
        <p class="px-4 pb-2 text-[11px] font-semibold tracking-widest text-muted uppercase">Workspace</p>
        <a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif
            @class(['flex min-h-12 items-center gap-3 rounded-[20px] px-4 text-sm font-medium transition', 'bg-ink text-paper' => request()->routeIs('dashboard'), 'text-muted hover:bg-canvas hover:text-ink' => ! request()->routeIs('dashboard')])>
            <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>
            Overview
        </a>
        @if ($user->company_id)
            <a href="{{ route('job-postings.index') }}" @if (request()->routeIs('job-postings.*')) aria-current="page" @endif
                @class(['flex min-h-12 items-center gap-3 rounded-[20px] px-4 text-sm font-medium transition', 'bg-ink text-paper' => request()->routeIs('job-postings.*'), 'text-muted hover:bg-canvas hover:text-ink' => ! request()->routeIs('job-postings.*')])>
                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Job postings
            </a>
            <a href="{{ route('forms.index') }}" @if (request()->routeIs('forms.*')) aria-current="page" @endif
                @class(['flex min-h-12 items-center gap-3 rounded-[20px] px-4 text-sm font-medium transition', 'bg-ink text-paper' => request()->routeIs('forms.*'), 'text-muted hover:bg-canvas hover:text-ink' => ! request()->routeIs('forms.*')])>
                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M8 4h8l4 4v12H4V4h4Z" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 11h8M8 15h5" stroke-linecap="round"/></svg>
                Forms
            </a>
        @endif
        <a href="{{ $user->company_id ? route('dashboard').'#company' : route('companies.create') }}" @if (request()->routeIs('companies.*')) aria-current="page" @endif
            @class(['flex min-h-12 items-center gap-3 rounded-[20px] px-4 text-sm font-medium transition', 'bg-ink text-paper' => request()->routeIs('companies.*'), 'text-muted hover:bg-canvas hover:text-ink' => ! request()->routeIs('companies.*')])>
            <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M3 21h18M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16M9 21v-4h6v4M9 7h1m4 0h1M9 11h1m4 0h1" stroke-linecap="round" stroke-linejoin="round"/></svg>
            {{ $user->company_id ? 'Company' : 'Company setup' }}
        </a>
        <a href="{{ route('dashboard') }}#account" class="flex min-h-12 items-center gap-3 rounded-[20px] px-4 text-sm font-medium text-muted transition hover:bg-canvas hover:text-ink">
            <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2" stroke-linecap="round"/></svg>
            Account overview
        </a>
    </nav>

    <div class="mt-auto flex flex-col gap-6">
        <div class="flex flex-col gap-3 rounded-[24px] bg-canvas p-5">
            <span class="size-2 rounded-full bg-accent" aria-hidden="true"></span>
            <p class="text-sm font-medium">Good teams start with people.</p>
            <p class="text-xs leading-relaxed text-muted">Your space for more thoughtful hiring.</p>
        </div>
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
                <button type="submit" class="flex min-h-11 w-full items-center justify-between rounded-[20px] px-4 text-sm text-muted transition hover:bg-canvas hover:text-ink">Log out <span aria-hidden="true">↗</span></button>
            </form>
        </div>
    </div>
</div>
