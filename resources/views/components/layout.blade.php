@props(['title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} · Roku</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-white">
        <a href="#main-content" class="sr-only z-50 rounded-xl bg-black px-4 py-2 text-white focus:not-sr-only focus:fixed focus:top-4 focus:left-4">Skip to content</a>

        <header class="sticky top-0 z-40 border-b border-line/60 bg-white/90 backdrop-blur-md">
            <div class="mx-auto flex h-14 max-w-5xl items-center justify-between gap-4 px-6">
                <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="flex items-center gap-0.5" aria-label="Roku home">
                    <span class="flex size-6 items-center justify-center rounded-xs bg-black text-[15px] leading-[15px] font-bold text-white" aria-hidden="true">R</span>
                    <span class="text-sm font-semibold tracking-[-0.35px]" aria-hidden="true">oku</span>
                </a>

                <nav aria-label="Main navigation" class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="px-2 py-1 text-xs font-medium text-zinc-600 transition hover:text-black">Workspace</a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="rounded-xl border border-zinc-300 bg-white px-4 py-1.5 text-xs font-medium text-zinc-800 transition hover:border-zinc-500">Log out</button>
                        </form>
                    @else
                        @if (request()->routeIs('register'))
                            <span class="hidden text-xs text-muted md:inline">Already have an account?</span>
                            <a href="{{ route('login') }}" class="px-2 py-1 text-xs font-medium text-zinc-600 transition hover:text-black">Log in</a>
                        @else
                            <a href="{{ route('register') }}" class="flex items-center gap-1.5 rounded-xl bg-brand px-4 py-1.5 text-xs font-medium text-white drop-shadow-[0_1px_1px_rgba(0,0,0,0.05)] transition hover:bg-brand/90">
                                Sign up
                                <img src="{{ asset('images/landing/arrow-right-white.svg') }}" alt="" width="16" height="16" class="size-4">
                            </a>
                        @endif
                    @endauth
                </nav>
            </div>
        </header>

        <div class="mx-auto flex min-h-[calc(100vh-3.5rem)] max-w-5xl flex-col gap-8 px-6 py-10 sm:gap-12">
            <main id="main-content" class="flex flex-1 flex-col" tabindex="-1">
                @if (session('status'))
                    <div role="status" class="mb-6 rounded-xl border border-line bg-paper px-6 py-4 text-sm">{{ session('status') }}</div>
                @endif

                {{ $slot }}
            </main>

            <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-line pt-6 text-xs text-zinc-400">
                <p>© {{ now()->year }} Roku, Inc.</p>
                <p class="font-mono">AI-assisted. Human-led.</p>
            </footer>
        </div>
    </body>
</html>
