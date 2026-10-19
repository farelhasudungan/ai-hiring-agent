<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Roku mengubah lamaran dari Gmail dan form menjadi ranking kandidat dengan alasan yang jelas. AI membantu menilai, Anda tetap memutuskan. Coba demo interaktifnya.">
    <meta name="theme-color" content="#ffffff">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <title>@yield('title', 'Roku: Dari kotak masuk ke shortlist')</title>
    <x-theme-script />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400..500&family=Source+Serif+4:ital,wght@0,400;1,400&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:rounded-full focus:bg-ink focus:p-4 focus:text-canvas">Lewati ke konten</a>
    <header class="relative z-30">
        <nav class="page-width flex min-h-18 flex-wrap items-center justify-between gap-2 px-4 py-4 sm:px-6 md:grid md:grid-cols-[1fr_auto_1fr] md:px-8" aria-label="Navigasi utama">
            <a href="{{ route('home') }}" class="justify-self-start font-serif text-3xl leading-none tracking-[-0.06em] sm:text-4xl" aria-label="Roku — Beranda">roku<span class="ml-0.5 inline-block size-1.5 rounded-full bg-ink" aria-hidden="true"></span></a>
            <div class="ml-auto flex shrink-0 items-center gap-1 md:order-last md:gap-2 md:justify-self-end">
                <a href="{{ route('filament.admin.auth.login') }}" class="inline-flex min-h-11 items-center rounded-full px-2 text-xs font-[450] whitespace-nowrap transition-colors hover:bg-surface sm:px-3 sm:text-sm">Sign In</a>
                <a href="{{ route('filament.admin.auth.register') }}" class="button-primary min-h-11 px-4 py-2 text-xs whitespace-nowrap sm:px-5 sm:text-sm">Get started</a>
                <button type="button" class="flex size-11 items-center justify-center rounded-full hover:bg-surface md:hidden" data-menu-toggle aria-expanded="false" aria-controls="main-navigation" aria-label="Buka menu navigasi">
                    <x-roku-icon name="menu" />
                </button>
            </div>
            <div id="main-navigation" class="hidden w-full flex-col gap-1 md:flex md:w-auto md:flex-row md:items-center md:gap-6">
                <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif class="rounded-full px-3 py-3 text-sm transition-colors hover:bg-surface md:py-2">Project</a>
                <a href="{{ route('about') }}" @if(request()->routeIs('about')) aria-current="page" @endif class="rounded-full px-3 py-3 text-sm text-body transition-colors hover:bg-surface hover:text-ink md:py-2">About</a>
            </div>
        </nav>
    </header>
    <main id="main" class="flex-1">@yield('content')</main>
    <footer class="border-t border-hairline">
        <div class="page-width grid gap-10 py-12 md:grid-cols-[2fr_1fr_1fr] md:py-16">
            <div>
                <a href="{{ route('home') }}" class="font-serif text-4xl tracking-[-0.06em]" aria-label="Roku — Beranda">roku.</a>
                <p class="mt-4 max-w-xs text-sm leading-6 text-body">Dari kotak masuk ke shortlist.<br>Lebih banyak waktu untuk mengenal manusia di balik CV.</p>
            </div>
            <div>
                <p class="mb-4 text-sm font-medium">Produk</p>
                <div class="flex flex-col items-start gap-3 text-sm text-body">
                    <a class="underline-offset-4 hover:text-ink hover:underline" href="{{ route('home') }}#cara-kerja">Cara kerja</a>
                    <a class="underline-offset-4 hover:text-ink hover:underline" href="{{ route('home') }}#demo">Demo interaktif</a>
                    <a class="underline-offset-4 hover:text-ink hover:underline" href="{{ route('home') }}#faq">Tanya jawab</a>
                </div>
            </div>
            <div>
                <p class="mb-4 text-sm font-medium">Di balik Roku</p>
                <a class="text-sm text-body underline-offset-4 hover:text-ink hover:underline" href="{{ route('about') }}">About / Tim Kami →</a>
                <p class="mt-4 text-xs leading-5 text-muted">Proyek Recruiting and Hiring Agents.<br>Dikembangkan oleh Kelompok 09, ITS.</p>
            </div>
        </div>
        <div class="page-width flex flex-wrap justify-between gap-3 pb-24 text-xs text-muted">
            <span>© {{ date('Y') }} Roku</span>
            <span>AI membantu. Manusia memutuskan.</span>
        </div>
    </footer>
    <button type="button" class="fixed right-[max(1.5rem,env(safe-area-inset-right))] bottom-[max(1.5rem,env(safe-area-inset-bottom))] z-40 flex size-12 items-center justify-center rounded-full border border-hairline bg-elevated text-ink transition-colors hover:bg-surface" data-theme-toggle aria-label="Aktifkan dark mode" aria-pressed="false">
        <x-roku-icon name="sun" class="theme-icon-light" />
        <x-roku-icon name="moon" class="theme-icon-dark" />
    </button>
</body>
</html>
