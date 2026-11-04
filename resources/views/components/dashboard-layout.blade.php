@props(['title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} · Hiring agent</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen">
        <a href="#main-content" class="sr-only z-50 rounded-full bg-ink px-6 py-3 text-paper focus:not-sr-only focus:fixed focus:top-4 focus:left-4">Skip to content</a>

        <aside class="fixed inset-y-0 left-0 hidden w-64 overflow-y-auto border-r border-line/60 bg-paper lg:block xl:w-72" aria-label="Sidebar">
            <x-dashboard-sidebar :user="auth()->user()" />
        </aside>

        <dialog id="workspace-menu" aria-label="Workspace menu" class="fixed inset-y-0 left-0 m-0 h-dvh max-h-none w-80 max-w-[calc(100%-2rem)] overflow-y-auto border-r border-line bg-paper text-ink backdrop:bg-ink/40">
            <div class="flex justify-end px-6 pt-4">
                <button type="button" data-close-workspace-menu class="flex min-h-11 items-center gap-3 rounded-full border border-line px-4 text-sm">Close menu <span aria-hidden="true">×</span></button>
            </div>
            <x-dashboard-sidebar :user="auth()->user()" />
        </dialog>

        <div class="min-w-0 lg:pl-64 xl:pl-72">
            <header class="sticky top-0 z-30 flex min-h-20 items-center justify-between gap-4 border-b border-line/60 bg-canvas/95 px-4 backdrop-blur sm:px-8 xl:px-12">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" data-open-workspace-menu aria-controls="workspace-menu" aria-expanded="false" aria-haspopup="dialog" aria-label="Open navigation menu" class="flex size-11 shrink-0 items-center justify-center rounded-full border border-line bg-paper lg:hidden">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/></svg>
                    </button>
                    <p class="truncate text-sm"><span class="hidden text-muted sm:inline">Workspace <span class="px-3 text-line">/</span></span>{{ $title }}</p>
                </div>
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full border border-line bg-paper text-sm font-medium" aria-label="Signed in as {{ auth()->user()->name }}">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
            </header>

            <main id="main-content" class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-8 sm:px-8 sm:py-10 xl:px-12" tabindex="-1">
                @if (session('status'))
                    <div role="status" class="rounded-[20px] border border-line bg-paper px-6 py-4 text-sm">{{ session('status') }}</div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
