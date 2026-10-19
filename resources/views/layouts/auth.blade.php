<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="referrer" content="no-referrer">
    <meta name="theme-color" content="#ffffff">
    <title>{{ $livewire->getTitle() }} — Roku</title>
    <x-theme-script />
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400..500&family=Source+Serif+4:ital,wght@0,400;1,400&display=swap" rel="stylesheet">
    @filamentStyles
    {{ filament()->getTheme()->getHtml() }}
    @livewireStyles
    @vite('resources/css/app.css')
</head>
<body class="roku-auth flex min-h-dvh flex-col bg-canvas font-sans text-ink antialiased">
    <a href="#auth-form" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:rounded-full focus:bg-ink focus:p-4 focus:text-canvas">Lewati ke formulir</a>
    <header class="page-width flex min-h-20 shrink-0 items-center justify-between gap-4 py-4">
        <a href="{{ route('home') }}" class="font-serif text-4xl tracking-[-0.06em]" aria-label="Roku — Beranda">roku<span class="ml-0.5 inline-block size-1.5 rounded-full bg-ink" aria-hidden="true"></span></a>
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" class="text-link text-muted" aria-label="Kembali ke beranda"><span class="hidden sm:inline">Kembali ke beranda</span><span class="sm:hidden">Beranda</span> <span aria-hidden="true">↗</span></a>
            <button type="button" data-theme-toggle class="flex size-11 shrink-0 items-center justify-center rounded-full text-ink transition-colors hover:bg-surface" aria-label="Aktifkan dark mode" aria-pressed="false">
                <x-roku-icon name="sun" class="theme-icon-light" />
                <x-roku-icon name="moon" class="theme-icon-dark" />
            </button>
        </div>
    </header>
    <main class="page-width flex-1 pb-6 lg:flex lg:flex-col lg:justify-center lg:py-4">
        {{ $slot }}
    </main>
    @livewire('notifications')
    @filamentScripts(withCore: true)
    @livewireScripts
</body>
</html>
