<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Recruiting and Hiring Agents membantu tim HR merangkum lamaran, menemukan kandidat yang tepat, dan menyusun shortlist dengan dukungan AI. Jelajahi demo produknya.">
    <title>@yield('title', 'Recruiting and Hiring Agents')</title>
    <script>
        (() => {
            let theme;
            try { theme = localStorage.getItem('theme'); } catch {}
            if (theme !== 'dark' && theme !== 'light') {
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.dataset.theme = theme;
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono&family=Instrument+Serif:ital@0;1&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-ink focus:p-4 focus:text-canvas">Lewati ke konten</a>
    <header class="sticky top-0 z-30 border-b border-hairline bg-canvas">
        <nav class="page-width flex min-h-18 flex-wrap items-center justify-between gap-3 py-3" aria-label="Navigasi utama">
            <a href="{{ route('home') }}" class="flex items-center gap-3 text-lg tracking-tight" aria-label="Recruiting and Hiring Agents — Beranda">
                <span class="flex size-8 items-center justify-center rounded-[3px] border border-ink font-mono text-sm" aria-hidden="true">&gt;_</span>
                Hiring Agents
            </a>
            <div class="flex items-center gap-2 md:order-last">
                <button type="button" class="theme-toggle flex size-11 items-center justify-center rounded-[3px] border border-hairline hover:bg-surface" data-theme-toggle aria-label="Aktifkan light mode" aria-pressed="false">
                    <svg class="theme-icon-light" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/></svg>
                    <svg class="theme-icon-dark" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M20.5 13A8.5 8.5 0 0 1 11 3.5 8.5 8.5 0 1 0 20.5 13Z"/></svg>
                </button>
                <button type="button" class="flex size-11 items-center justify-center rounded-[3px] border border-hairline md:hidden" data-menu-toggle aria-expanded="false" aria-controls="main-navigation" aria-label="Buka menu navigasi">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
            <div id="main-navigation" class="hidden w-full flex-col gap-1 md:flex md:w-auto md:flex-row md:items-center md:gap-2">
                @foreach (['home' => 'Project', 'about' => 'About'] as $name => $label)
                    <a href="{{ route($name) }}" @if(request()->routeIs($name)) aria-current="page" @endif class="rounded-[3px] px-4 py-3 text-sm transition-colors hover:bg-surface md:py-2 {{ request()->routeIs($name) ? 'text-ink bg-surface' : 'text-body' }}">{{ $label }}</a>
                @endforeach
            </div>
        </nav>
    </header>
    <main id="main" class="flex-1">@yield('content')</main>
    <footer class="border-t border-hairline">
        <div class="page-width grid gap-10 py-12 md:grid-cols-[2fr_1fr]">
            <div><a href="{{ route('home') }}" class="text-lg tracking-tight">Recruiting and Hiring Agents</a><p class="mt-4 max-w-sm text-sm leading-6 text-body">Lebih sedikit screening. Lebih banyak talenta tepat.<br>Partner AI untuk langkah rekrutmen berikutnya.</p></div>
            <div><p class="eyebrow mb-4">Jelajahi</p><div class="flex flex-col items-start gap-3 text-sm text-body"><a class="hover:text-ink" href="{{ route('home') }}">Project</a><a class="hover:text-ink" href="{{ route('about') }}">About / Tim Kami</a></div></div>
        </div>
        <div class="page-width flex flex-wrap justify-between gap-3 pb-8 font-mono text-xs text-muted"><span>© {{ date('Y') }} Hiring Agents</span><span>Built for people. Powered by AI.</span></div>
    </footer>
</body>
</html>
