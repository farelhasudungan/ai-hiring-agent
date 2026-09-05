@extends('layouts.app')

@section('title', 'Rencana Proyek Akhir: Database Health Checker & Performance Monitor Agent')

@section('content')
<div class="relative">

    <section class="mx-auto max-w-5xl px-6 pt-12 pb-8 sm:pt-16">
        <div class="mb-5 flex flex-wrap items-center gap-3">
            <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 font-mono text-xs font-medium text-emerald-600 dark:text-emerald-400">
                <span class="size-2 rounded-full bg-emerald-500"></span>
                <span>Rencana Proyek Akhir</span>
            </div>
            <span class="font-mono text-xs text-zinc-500 dark:text-zinc-400">Laravel + Livewire + NativePHP Desktop App</span>
        </div>

        <h1 class="max-w-4xl text-3xl font-semibold leading-[1.08] tracking-tight text-zinc-900 dark:text-white sm:text-4xl lg:text-5xl font-display">
            Database Health Checker & <br class="hidden sm:inline">
            <span class="gradient-text font-bold">Performance Monitor Agent</span>
        </h1>

        <p class="mt-5 max-w-3xl text-base leading-relaxed text-zinc-600 dark:text-zinc-400 sm:text-lg">
            Platform Agentic AI berbasis Laravel yang membantu administrator basis data memantau kesehatan database,
            membaca metrik performa, dan memahami rekomendasi optimasi melalui antarmuka chat interaktif.
            Aplikasi dikemas sebagai standalone desktop application menggunakan NativePHP.
        </p>

        <div class="mt-7 grid gap-3 text-xs font-mono sm:grid-cols-3">
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900/60">
                <span class="block text-zinc-500">Target User</span>
                <span class="mt-1 block font-semibold text-zinc-900 dark:text-white">Database Administrator</span>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900/60">
                <span class="block text-zinc-500">Interaction Model</span>
                <span class="mt-1 block font-semibold text-zinc-900 dark:text-white">Chat-based Diagnostics</span>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900/60">
                <span class="block text-zinc-500">AI Engine</span>
                <span class="mt-1 block font-semibold text-zinc-900 dark:text-white">Ollama / Senopati AI ITS</span>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-5xl px-6 py-6">
        <div class="grid gap-5 lg:grid-cols-[0.95fr_1.05fr]">
            <div class="rounded-xl border border-rose-500/25 bg-rose-50/50 p-6 dark:bg-rose-950/10">
                <span class="font-mono text-xs font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-400">Masalah yang Diangkat</span>
                <h2 class="mt-3 text-xl font-semibold tracking-tight text-zinc-900 dark:text-white font-display">Metrik database sering terlalu mentah untuk keputusan cepat.</h2>
                <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                    Administrator perlu membaca koneksi aktif, ukuran tabel, slow query log, dan hasil diagnostik secara manual.
                    Saat terjadi lonjakan beban, proses ini membuat penyebab masalah lebih lambat ditemukan.
                </p>
            </div>

            <div class="rounded-xl border border-blue-500/25 bg-blue-50/60 p-6 dark:bg-blue-950/20">
                <span class="font-mono text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Solusi Agentic AI</span>
                <h2 class="mt-3 text-xl font-semibold tracking-tight text-zinc-900 dark:text-white font-display">Agent membaca metrik, menjalankan diagnosis, lalu menjelaskan hasilnya.</h2>
                <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                    Pengguna cukup bertanya melalui chat. Agent akan mengambil metrik yang relevan, menjalankan task backend seperti
                    <code class="rounded bg-blue-100 px-1.5 py-0.5 font-mono text-xs text-blue-800 dark:bg-blue-900/50 dark:text-blue-200">EXPLAIN</code>
                    dan
                    <code class="rounded bg-blue-100 px-1.5 py-0.5 font-mono text-xs text-blue-800 dark:bg-blue-900/50 dark:text-blue-200">SHOW PROCESSLIST</code>,
                    kemudian menyusun rekomendasi optimasi dalam bahasa natural.
                </p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-5xl px-6 py-8">
        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-white font-display">Fitur Utama Platform</h2>
                <p class="mt-1 font-mono text-xs text-zinc-500 dark:text-zinc-400">monitoring, diagnostics, AI explanation, alerting</p>
            </div>
            <span class="tag-pill w-fit">Agent Capabilities</span>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/60 dark:shadow-none">
                <div class="mb-3 flex size-9 items-center justify-center rounded-lg border border-blue-500/20 bg-blue-500/10 font-mono text-xs font-bold text-blue-600 dark:text-blue-400">01</div>
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Monitoring Kesehatan Database</h3>
                <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Menampilkan koneksi aktif, ukuran tabel, status query, dan indikasi beban database secara real-time.</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/60 dark:shadow-none">
                <div class="mb-3 flex size-9 items-center justify-center rounded-lg border border-purple-500/20 bg-purple-500/10 font-mono text-xs font-bold text-purple-600 dark:text-purple-400">02</div>
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Chat Interaktif Livewire</h3>
                <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Pengguna berinteraksi dengan agent melalui UI chat reaktif yang terasa ringan dan langsung.</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/60 dark:shadow-none">
                <div class="mb-3 flex size-9 items-center justify-center rounded-lg border border-emerald-500/20 bg-emerald-500/10 font-mono text-xs font-bold text-emerald-600 dark:text-emerald-400">03</div>
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Task Diagnostik Otomatis</h3>
                <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Agent dapat menjalankan diagnostic query terkontrol seperti EXPLAIN dan SHOW PROCESSLIST.</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/60 dark:shadow-none">
                <div class="mb-3 flex size-9 items-center justify-center rounded-lg border border-amber-500/20 bg-amber-500/10 font-mono text-xs font-bold text-amber-600 dark:text-amber-400">04</div>
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Analisis LLM Dual Engine</h3>
                <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Mendukung API Ollama lokal dan API Senopati AI ITS untuk analisis metrik dan penyusunan respons.</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/60 dark:shadow-none">
                <div class="mb-3 flex size-9 items-center justify-center rounded-lg border border-cyan-500/20 bg-cyan-500/10 font-mono text-xs font-bold text-cyan-600 dark:text-cyan-400">05</div>
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Rekomendasi Optimasi</h3>
                <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Hasil query mentah diterjemahkan menjadi saran seperti indeks, tuning query, atau investigasi koneksi.</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/60 dark:shadow-none">
                <div class="mb-3 flex size-9 items-center justify-center rounded-lg border border-rose-500/20 bg-rose-500/10 font-mono text-xs font-bold text-rose-600 dark:text-rose-400">06</div>
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Alert Anomali</h3>
                <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Memberi peringatan ketika koneksi, penggunaan disk, atau pola query menunjukkan lonjakan tidak wajar.</p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-5xl px-6 py-8">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/60 dark:shadow-none">
            <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-white font-display">Alur Kerja Agent</h2>
                    <p class="mt-1 font-mono text-xs text-zinc-500 dark:text-zinc-400">from database metrics to readable recommendation</p>
                </div>
                <span class="font-mono text-xs text-zinc-500 dark:text-zinc-400">real-time diagnostic loop</span>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-950/60">
                    <span class="font-mono text-[11px] font-semibold text-blue-600 dark:text-blue-400">STEP 1</span>
                    <h3 class="mt-2 text-sm font-bold text-zinc-900 dark:text-white">User bertanya</h3>
                    <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Admin mengirim pertanyaan performa melalui chat.</p>
                </div>
                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-950/60">
                    <span class="font-mono text-[11px] font-semibold text-purple-600 dark:text-purple-400">STEP 2</span>
                    <h3 class="mt-2 text-sm font-bold text-zinc-900 dark:text-white">Agent ambil metrik</h3>
                    <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Backend membaca status database dan slow query log.</p>
                </div>
                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-950/60">
                    <span class="font-mono text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">STEP 3</span>
                    <h3 class="mt-2 text-sm font-bold text-zinc-900 dark:text-white">LLM menganalisis</h3>
                    <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Ollama atau Senopati AI ITS menyusun interpretasi.</p>
                </div>
                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-950/60">
                    <span class="font-mono text-[11px] font-semibold text-amber-600 dark:text-amber-400">STEP 4</span>
                    <h3 class="mt-2 text-sm font-bold text-zinc-900 dark:text-white">Rekomendasi tampil</h3>
                    <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Sistem menampilkan penyebab masalah dan langkah optimasi.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-5xl px-6 py-8 pb-24">
        <div class="grid gap-5 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/60 dark:shadow-none">
                <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Rancangan Arsitektur Ringkas</h2>
                <div class="mt-5 grid gap-3 text-xs font-mono sm:grid-cols-2">
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800/80 dark:bg-zinc-950/60">
                        <span class="block text-zinc-500">Desktop Shell</span>
                        <span class="mt-1 block font-semibold text-zinc-900 dark:text-white">NativePHP</span>
                    </div>
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800/80 dark:bg-zinc-950/60">
                        <span class="block text-zinc-500">Application Layer</span>
                        <span class="mt-1 block font-semibold text-zinc-900 dark:text-white">Laravel</span>
                    </div>
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800/80 dark:bg-zinc-950/60">
                        <span class="block text-zinc-500">Reactive Interface</span>
                        <span class="mt-1 block font-semibold text-zinc-900 dark:text-white">Laravel Livewire</span>
                    </div>
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800/80 dark:bg-zinc-950/60">
                        <span class="block text-zinc-500">LLM Provider</span>
                        <span class="mt-1 block font-semibold text-zinc-900 dark:text-white">Ollama / Senopati AI ITS</span>
                    </div>
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800/80 dark:bg-zinc-950/60">
                        <span class="block text-zinc-500">Database Target</span>
                        <span class="mt-1 block font-semibold text-zinc-900 dark:text-white">MySQL-compatible DB</span>
                    </div>
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800/80 dark:bg-zinc-950/60">
                        <span class="block text-zinc-500">Diagnostics</span>
                        <span class="mt-1 block font-semibold text-zinc-900 dark:text-white">EXPLAIN / PROCESSLIST</span>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-amber-500/30 bg-amber-50/50 p-6 dark:bg-amber-950/20">
                <span class="tag-pill !text-amber-600 dark:!text-amber-400">Value Added</span>
                <h2 class="mt-4 text-lg font-bold text-zinc-900 dark:text-white">Automatic Alerting & Anomaly Detection</h2>
                <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                    Sistem dapat mengirim peringatan ketika mendeteksi lonjakan koneksi, penggunaan disk yang tidak wajar,
                    atau pola query lambat yang berulang. Fitur ini membuat agent tidak hanya menjawab saat ditanya,
                    tetapi juga aktif membantu mendeteksi risiko performa sejak awal.
                </p>
            </div>
        </div>
    </section>

</div>
@endsection
