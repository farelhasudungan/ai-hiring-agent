<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Profil Kelompok 9, Teknik Informatika ITS — ruang eksplorasi framework dan Agentic AI.">
    <title>@yield('title', 'ITS Academic') — PBKK Sandbox</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono&family=Instrument+Serif:ital@0;1&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-ink focus:p-4 focus:text-canvas">Lewati ke konten</a>
    <header class="sticky top-0 z-30 border-b border-hairline bg-canvas">
        <nav class="page-width flex min-h-18 flex-wrap items-center justify-between gap-3 py-3" aria-label="Navigasi utama">
            <a href="{{ route('home') }}" class="flex items-center gap-3 text-lg tracking-tight" aria-label="ITS Academic — Beranda">
                <span class="flex size-8 items-center justify-center rounded-[3px] border border-ink font-mono text-sm" aria-hidden="true">&gt;_</span>
                its.academic
            </a>
            <button type="button" class="flex size-11 items-center justify-center rounded-[3px] border border-hairline md:hidden" data-menu-toggle aria-expanded="false" aria-controls="main-navigation" aria-label="Buka menu navigasi">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
            <div id="main-navigation" class="hidden w-full flex-col gap-1 md:flex md:w-auto md:flex-row md:items-center md:gap-2">
                @foreach (['home' => 'Tim kami',  'project' => 'Proyek AI', 'about' => 'Departemen'] as $name => $label)
                    <a href="{{ route($name) }}" @if(request()->routeIs($name)) aria-current="page" @endif class="rounded-[3px] px-4 py-3 text-sm transition-colors hover:bg-surface md:py-2 {{ request()->routeIs($name) ? 'text-ink bg-surface' : 'text-body' }}">{{ $label }}</a>
                @endforeach
                <a href="{{ route('hitung', ['angka1' => 10, 'angka2' => 5, 'operasi' => 'tambah']) }}" class="button-primary md:ml-4">Coba kalkulator <span aria-hidden="true">↗</span></a>
            </div>
        </nav>
    </header>
    <main id="main" class="flex-1">@yield('content')</main>
    <footer class="border-t border-hairline">
        <div class="page-width grid gap-10 py-12 md:grid-cols-[2fr_1fr_1fr]">
            <div><a href="{{ route('home') }}" class="text-lg tracking-tight">its.academic</a><p class="mt-4 max-w-sm text-sm leading-6 text-body">Ruang belajar, bereksperimen, dan membangun.<br>Kelompok 9 · Pemrograman Berbasis Kerangka Kerja.</p></div>
            <div><p class="eyebrow mb-4">Jelajahi</p><div class="flex flex-col items-start gap-3 text-sm text-body"><a class="hover:text-ink" href="{{ route('about') }}">Departemen</a><a class="hover:text-ink" href="{{ route('project') }}">Rencana proyek AI ↗</a></div></div>
            <div><p class="eyebrow mb-4">Institut Teknologi Sepuluh Nopember</p><div class="flex flex-col items-start gap-3 text-sm text-body"><a class="hover:text-ink" href="https://www.its.ac.id" target="_blank" rel="noreferrer">Portal ITS ↗</a><a class="hover:text-ink" href="https://www.its.ac.id/informatika" target="_blank" rel="noreferrer">Teknik Informatika ↗</a></div></div>
        </div>
        <div class="page-width flex flex-wrap justify-between gap-3 pb-8 font-mono text-xs text-muted"><span>© {{ date('Y') }} Kelompok 9</span><span>Surabaya, Indonesia · Laravel 12 / Tailwind CSS</span></div>
    </footer>
</body>
</html>
