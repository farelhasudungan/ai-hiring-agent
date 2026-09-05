@extends('layouts.app')

@section('title', 'Profil Departemen Teknik Informatika ITS')

@section('content')
<div class="relative">

    <!-- Hero Section -->
    <section class="mx-auto max-w-5xl px-6 pt-12 pb-8 sm:pt-20">
        <div class="inline-flex items-center gap-2 rounded-full border border-purple-500/30 bg-purple-500/10 px-3 py-1 font-mono text-xs font-medium text-purple-600 dark:text-purple-400 mb-5">
            <span class="size-2 rounded-full bg-purple-500"></span>
            <span>FTEIC - Institut Teknologi Sepuluh Nopember</span>
        </div>

        <h1 class="text-4xl font-semibold leading-[1.08] tracking-tight sm:text-5xl text-zinc-900 dark:text-white font-display">
            Departemen <span class="gradient-text font-bold">Teknik Informatika</span>
        </h1>

        <p class="mt-5 max-w-2xl text-base sm:text-lg text-zinc-600 dark:text-zinc-400 leading-relaxed">
            Departemen Teknik Informatika di ITS berdedikasi pada keunggulan dalam pendidikan, penelitian, dan inovasi di bidang ilmu komputer dan rekayasa perangkat lunak. Kami mempersiapkan mahasiswa untuk menjadi pemimpin teknologi melalui kurikulum mutakhir dan fasilitas kelas dunia.
        </p>
    </section>

    <!-- Vision & Mission Grid -->
    <section class="mx-auto max-w-5xl px-6 py-10">
        <div class="grid gap-6 sm:grid-cols-2">
            
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/60 p-6 shadow-xs dark:shadow-none">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-600 dark:text-blue-400 font-mono text-xs font-bold">
                        01
                    </div>
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Visi Departemen</h2>
                </div>
                <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                    Visi Departemen Teknik Informatika (DTIF) berfokus untuk menjadi lembaga pendidikan yang unggul dan inovatif-inventif dalam informatika cerdas, mendukung transformasi digital dan berkontribusi kepada masyarakat dengan reputasi internasional.
                </p>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/60 p-6 shadow-xs dark:shadow-none">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-600 dark:text-purple-400 font-mono text-xs font-bold">
                        02
                    </div>
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Misi Utama</h2>
                </div>
                <ul class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 space-y-2">
                    <li class="flex items-start gap-2">
                        <span class="text-purple-600 dark:text-purple-400">&bull;</span>
                        <span>Menyelenggarakan proses pembelajaran yang berkualitas, dan memenuhi standar nasional maupun internasional.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-purple-600 dark:text-purple-400">&bull;</span>
                        <span>Melaksanakan penelitian yang inovatif, bermutu, dan bermanfaat.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-purple-600 dark:text-purple-400">&bull;</span>
                        <span>Meningkatkan pemanfaatan teknologi informasi dan komunikasi untuk masyarakat.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-purple-600 dark:text-purple-400">&bull;</span>
                        <span>Menjalin kemitraan dengan berbagai lembaga, baik di dalam maupun di luar negeri.</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Laboratory Grid -->
    <section class="mx-auto max-w-5xl px-6 py-10 pb-24">
        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-white font-display">Laboratorium Departemen</h2>
                <p class="mt-1 font-mono text-xs text-zinc-500 dark:text-zinc-400">8 fokus riset dan praktikum pendukung pembelajaran</p>
            </div>
            <span class="tag-pill w-fit">8 Laboratorium</span>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/60 p-5 shadow-xs dark:shadow-none">
                <div class="mb-3 flex size-9 items-center justify-center rounded-lg border border-blue-500/20 bg-blue-500/10 font-mono text-xs font-bold text-blue-600 dark:text-blue-400">01</div>
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Rekayasa Perangkat Lunak</h3>
                <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Fokus pada analisis kebutuhan, desain sistem, pengujian, dan pengembangan aplikasi modern.</p>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/60 p-5 shadow-xs dark:shadow-none">
                <div class="mb-3 flex size-9 items-center justify-center rounded-lg border border-purple-500/20 bg-purple-500/10 font-mono text-xs font-bold text-purple-600 dark:text-purple-400">02</div>
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Komputasi Cerdas dan Visi</h3>
                <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Mendukung pembelajaran AI, machine learning, data mining, dan sistem berbasis pengetahuan.</p>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/60 p-5 shadow-xs dark:shadow-none">
                <div class="mb-3 flex size-9 items-center justify-center rounded-lg border border-emerald-500/20 bg-emerald-500/10 font-mono text-xs font-bold text-emerald-600 dark:text-emerald-400">03</div>
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Komputasi Berbasis Jaringan</h3>
                <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Membahas komunikasi data, administrasi jaringan, keamanan jaringan, dan infrastruktur terdistribusi.</p>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/60 p-5 shadow-xs dark:shadow-none">
                <div class="mb-3 flex size-9 items-center justify-center rounded-lg border border-amber-500/20 bg-amber-500/10 font-mono text-xs font-bold text-amber-600 dark:text-amber-400">04</div>
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Manajemen Cerdas Informasi</h3>
                <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Berfokus pada perancangan database, optimasi query, data warehouse, dan manajemen data.</p>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/60 p-5 shadow-xs dark:shadow-none">
                <div class="mb-3 flex size-9 items-center justify-center rounded-lg border border-cyan-500/20 bg-cyan-500/10 font-mono text-xs font-bold text-cyan-600 dark:text-cyan-400">05</div>
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Grafika, Interaksi, Gim dan Analitik</h3>
                <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Mendukung topik computer graphics, pengolahan citra, visualisasi, game, dan media interaktif.</p>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/60 p-5 shadow-xs dark:shadow-none">
                <div class="mb-3 flex size-9 items-center justify-center rounded-lg border border-rose-500/20 bg-rose-500/10 font-mono text-xs font-bold text-rose-600 dark:text-rose-400">06</div>
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Pemodelan dan Komputasi Terapan</h3>
                <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Mewadahi riset dan kerjasama industri di bidang pemodelan & simulasi, peramalan sains, optimasi, serta komputasional saintifik.</p>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/60 p-5 shadow-xs dark:shadow-none">
                <div class="mb-3 flex size-9 items-center justify-center rounded-lg border border-indigo-500/20 bg-indigo-500/10 font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">07</div>
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Algoritma dan Pemrograman</h3>
                <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Menjadi ruang penguatan struktur data, algoritma, pemrograman kompetitif, dan problem solving.</p>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/60 p-5 shadow-xs dark:shadow-none">
                <div class="mb-3 flex size-9 items-center justify-center rounded-lg border border-teal-500/20 bg-teal-500/10 font-mono text-xs font-bold text-teal-600 dark:text-teal-400">08</div>
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Teknologi Jaringan dan Keamanan Siber Cerdas</h3>
                <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Membahas keamanan aplikasi, kriptografi, digital forensics, dan perlindungan sistem informasi.</p>
            </div>
        </div>
    </section>
</div>
@endsection
