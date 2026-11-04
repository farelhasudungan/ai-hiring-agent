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
        <a href="#main-content" class="sr-only z-50 rounded-full bg-ink px-6 py-3 text-paper focus:not-sr-only focus:absolute focus:top-6 focus:left-6">Skip to content</a>

        <div class="mx-auto flex min-h-screen max-w-7xl flex-col gap-8 px-4 py-6 sm:gap-12 sm:px-8">
            <header class="flex min-h-20 flex-wrap items-center justify-between gap-4 rounded-[40px] bg-white px-6 py-4 sm:rounded-full sm:px-8">
                <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="inline-flex min-h-11 items-center gap-3 font-medium tracking-tight" aria-label="Hiring agent home">
                    <span class="flex size-10 items-center justify-center rounded-full bg-ink text-paper" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" class="size-6" stroke="currentColor" stroke-width="1.5">
                            <path d="M7 5v14M17 5v14M7 12h10" />
                            <circle cx="12" cy="12" r="10" />
                        </svg>
                    </span>
                    Hiring agent<span class="hidden text-muted sm:inline"> / a better way to hire</span>
                </a>

                <nav aria-label="Main navigation" class="flex items-center gap-4 sm:gap-6">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex min-h-11 items-center text-sm font-medium">Workspace</a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="min-h-11 rounded-full border border-line px-5 text-sm font-medium transition hover:border-ink">Log out</button>
                        </form>
                    @else
                        @if (request()->routeIs('register'))
                            <span class="hidden text-sm text-muted md:inline">Already have an account?</span>
                            <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center rounded-full border border-line px-5 text-sm font-medium transition hover:border-ink">Log in <span class="ml-3" aria-hidden="true">↗</span></a>
                        @else
                            <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center rounded-full border border-line px-5 text-sm font-medium transition hover:border-ink">Sign up <span class="ml-3" aria-hidden="true">↗</span></a>
                        @endif
                    @endauth
                </nav>
            </header>

            <main id="main-content" class="flex flex-1 flex-col" tabindex="-1">
                @if (session('status'))
                    <div role="status" class="mb-6 rounded-[20px] border border-line bg-paper px-6 py-4 text-sm">{{ session('status') }}</div>
                @endif

                {{ $slot }}
            </main>

            <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-2 pt-6 text-xs text-muted">
                <p>Thoughtful hiring starts here.</p>
                <p>AI-assisted. Human-led.</p>
            </footer>
        </div>
    </body>
</html>
