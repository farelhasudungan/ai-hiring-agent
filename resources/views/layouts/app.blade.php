<!DOCTYPE html>
<html lang="id" class="dark scroll-smooth antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ITS Academic Profile & Agentic AI Sandbox - Pemrograman Berbasis Kerangka Kerja">
    <title>@yield('title', 'ITS Academic Profile') - PBKK Sandbox</title>

    <script>
        (function() {
            const saved = localStorage.getItem('theme');
            if (saved === 'light' || (!saved && window.matchMedia('(prefers-color-scheme: light)').matches)) {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
        })();

        function toggleTheme(dark) {
            if (dark) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
            document.querySelectorAll('.neu-toggle').forEach(t => {
                t.dataset.index = dark ? 1 : 0;
                const opts = t.querySelectorAll('.neu-toggle__opt');
                if (opts.length >= 2) {
                    opts[0].dataset.active = !dark ? 'true' : 'false';
                    opts[1].dataset.active = dark ? 'true' : 'false';
                }
            });
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-[#0a0a0d] dark:text-zinc-100 font-sans antialiased selection:bg-blue-600/30 selection:text-white flex flex-col transition-colors duration-200">

    <div class="fixed top-0 left-0 right-0 h-1 z-50 overflow-hidden bg-zinc-900/40 backdrop-blur-xs pointer-events-none">
        <div class="loading-beam"></div>
    </div>

    <div class="relative flex min-h-screen flex-col">
        <header class="sticky top-0 z-50 border-b border-zinc-200 dark:border-zinc-800/80 bg-white/80 dark:bg-[#0a0a0d]/80 backdrop-blur-md transition-colors duration-200">
            <nav class="mx-auto flex h-14 max-w-5xl items-center justify-between gap-2 px-4 sm:px-6">
                <a class="group flex shrink-0 items-center gap-2 font-mono text-sm font-semibold tracking-tight" href="{{ route('home') }}">
                    <div class="glow-tile !w-8 !h-8 sm:!w-10 sm:!h-10 !rounded-full shrink-0 transition-transform group-hover:scale-105 border border-blue-500/30">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998A12.083 12.083 0 015.84 10.578L12 14z"/>
                        </svg>
                    </div>
                    <span class="hidden whitespace-nowrap md:inline text-zinc-900 dark:text-white">
                        its<span class="text-zinc-400 dark:text-zinc-500">.academic</span>
                    </span>
                </a>

                <div class="flex min-w-0 items-center gap-0.5 sm:gap-1 font-mono text-xs sm:text-sm">
                    <a href="{{ route('home') }}"
                       class="whitespace-nowrap rounded-md px-2.5 py-1.5 transition-colors {{ request()->routeIs('home') ? 'bg-zinc-200 dark:bg-zinc-800 text-zinc-900 dark:text-white font-medium' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}">
                        dashboard
                    </a>
                    <a href="{{ route('about') }}"
                       class="whitespace-nowrap rounded-md px-2.5 py-1.5 transition-colors {{ request()->routeIs('about') ? 'bg-zinc-200 dark:bg-zinc-800 text-zinc-900 dark:text-white font-medium' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}">
                        departemen
                    </a>
                    <a href="{{ route('project') }}"
                       class="whitespace-nowrap rounded-md px-2.5 py-1.5 transition-colors {{ request()->routeIs('project') ? 'bg-zinc-200 dark:bg-zinc-800 text-zinc-900 dark:text-white font-medium' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}">
                        proyek-ai
                    </a>

                    <div class="flex items-center gap-1 ml-1 sm:ml-2">
                        <span class="hidden sm:inline-block mx-1.5 h-5 w-px bg-zinc-200 dark:bg-zinc-800" aria-hidden="true"></span>

                        <div class="neu-toggle !p-1 scale-90" data-index="1" title="Toggle Tema Mode Terang / Gelap">
                            <div class="neu-toggle__thumb"></div>
                            <button type="button" onclick="toggleTheme(false)" class="neu-toggle__opt !w-8 !h-7" data-active="false" title="Mode Siang" aria-label="Mode Siang">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </button>
                            <button type="button" onclick="toggleTheme(true)" class="neu-toggle__opt !w-8 !h-7" data-active="true" title="Mode Malam" aria-label="Mode Malam">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </nav>
        </header>

        <main id="main" class="flex-1">
            @yield('content')
        </main>

        <footer class="mt-28 border-t border-zinc-200 dark:border-zinc-800/80 bg-zinc-100/50 dark:bg-transparent">
            <div class="mx-auto max-w-5xl px-6 py-16 font-mono text-sm text-zinc-600 dark:text-zinc-400">
                <div class="grid grid-cols-2 gap-x-10 gap-y-12 sm:grid-cols-[1.5fr_1.2fr_0.9fr_0.9fr]">
                    <div class="col-span-2 flex flex-col gap-5 sm:col-span-1">
                        <a href="{{ route('project') }}" class="group inline-flex w-fit items-center gap-2 rounded-full border border-zinc-300 dark:border-zinc-800 bg-white dark:bg-zinc-900/60 px-4 py-2 text-xs transition-colors hover:border-blue-500/50">
                            <span class="relative flex size-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-500 opacity-75"></span>
                                <span class="relative inline-flex size-2 rounded-full bg-blue-500"></span>
                            </span>
                            <span class="text-zinc-800 dark:text-zinc-200">building agentic AI @ ITS</span>
                            <span class="text-zinc-500 transition-transform group-hover:translate-x-0.5">&rarr;</span>
                        </a>
                        <p class="max-w-[32ch] leading-relaxed text-zinc-600 dark:text-zinc-400/80 text-xs">
                            Sistem Informasi Statik Profil Mahasiswa & Project Sandbox Pemrograman Berbasis Kerangka Kerja.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3.5">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-zinc-400 dark:text-zinc-400/60 font-semibold">Teknologi</p>
                        <span class="text-xs text-zinc-700 dark:text-zinc-300">Laravel 12</span>
                        <span class="text-xs text-zinc-700 dark:text-zinc-300">Tailwind CSS v4</span>
                        <span class="text-xs text-zinc-700 dark:text-zinc-300">Vite</span>
                    </div>

                    <div class="flex flex-col gap-3.5">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-zinc-400 dark:text-zinc-400/60 font-semibold">ITS Sukolilo</p>
                        <a href="https://www.its.ac.id" target="_blank" rel="noreferrer" class="text-xs text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">Portal ITS &rarr;</a>
                        <a href="https://www.its.ac.id/informatika" target="_blank" rel="noreferrer" class="text-xs text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">Teknik Informatika &rarr;</a>
                    </div>

                    <div class="flex flex-col gap-3.5">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-zinc-400 dark:text-zinc-400/60 font-semibold">Navigasi</p>
                        <a href="{{ route('home') }}" class="text-xs text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">dashboard</a>
                        <a href="{{ route('about') }}" class="text-xs text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">departemen</a>
                        <a href="{{ route('project') }}" class="text-xs text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">proyek-ai</a>
                    </div>
                </div>

                <div class="mt-16 flex flex-col items-start justify-between gap-4 border-t border-zinc-200 dark:border-zinc-800/60 pt-8 sm:flex-row sm:items-center text-xs">
                    <p>&copy; {{ date('Y') }} Kelompok 9 - Pemrograman Berbasis Kerangka Kerja.</p>
                    <div class="flex flex-wrap items-center gap-4 text-zinc-500 dark:text-zinc-400">
                        <span>Departemen Teknik Informatika</span>
                        <span>&bull;</span>
                        <span>FTEIC ITS</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>

</body>
</html>
