@extends('layouts.app')

@section('title', 'Profil Kelompok 9')

@section('content')
<div class="relative">

    <!-- Hero Section -->
    <section class="mx-auto max-w-5xl px-6 pt-12 pb-8 sm:pt-16">
        <div class="inline-flex items-center gap-2 rounded-full border border-blue-500/30 bg-blue-500/10 px-3 py-1 font-mono text-xs font-medium text-blue-600 dark:text-blue-400 mb-5">
            <span class="relative flex size-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex size-2 rounded-full bg-blue-500"></span>
            </span>
            <span>Tim Kelompok 9 Pemrograman Berbasis Kerangka Kerja</span>
        </div>

        <h1 class="text-4xl font-semibold leading-[1.08] tracking-tight sm:text-5xl text-zinc-900 dark:text-white font-display">
            Profil Tim Kelompok 9 <br class="hidden sm:inline"><span class="gradient-text font-bold">Pemrograman Berbasis Kerangka Kerja</span>
        </h1>

        <p class="mt-4 max-w-2xl text-base sm:text-lg text-zinc-600 dark:text-zinc-400 leading-relaxed">
            Perkenalan anggota kelompok pengembang aplikasi <span class="text-zinc-900 dark:text-white font-medium">ITS Academic Sandbox</span> dan perancang proyek <span class="text-blue-600 dark:text-blue-400 font-medium">Database Health Checker & Performance Monitor Agent</span>.
        </p>

        <!-- Quick CTA Buttons (Gloss Buttons) -->
        <div class="mt-6 flex flex-wrap items-center gap-3">
            <a href="{{ route('project') }}" 
               style="background: linear-gradient(180deg,#1e293b,#0f172a) padding-box, linear-gradient(180deg,#3b82f6,#1d4ed8) border-box"
               class="relative inline-flex items-center justify-center gap-2 rounded-lg font-medium text-white shadow-[0_1px_2px_rgba(0,0,0,0.35),0_6px_18px_-6px_rgba(59,130,246,0.6)] transition-all hover:brightness-110 active:translate-y-px h-10 px-5 text-sm border border-transparent">
                <span>Visi Proyek Agentic AI</span>
                <svg class="w-4 h-4 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>

            <a href="{{ route('about') }}" 
               style="background: linear-gradient(180deg,#201E25,#323137) padding-box, linear-gradient(180deg,#4B4951,#313036) border-box"
               class="relative inline-flex items-center justify-center gap-2 rounded-lg font-medium text-zinc-200 transition-all hover:brightness-110 active:translate-y-px h-10 px-5 text-sm border border-transparent shadow-[0_2px_4px_rgba(0,0,0,0.1)]">
                <span>Profil Departemen</span>
            </a>
        </div>
    </section>

    <!-- Highlight 6 Members Grid (Cards per Anggota) -->
    <section class="mx-auto max-w-5xl px-6 py-8">
        <div class="mb-6 flex items-end justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-white font-display">Anggota Tim Kelompok</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 font-mono mt-0.5">Teknik Informatika ITS</p>
            </div>
            <span class="tag-pill">6 Members</span>
        </div>

        <div class="grid gap-6 sm:grid-cols-3">
            
            <!-- Member 1 -->
            <div class="group relative overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 p-6 shadow-xs dark:shadow-none transition-all duration-200 hover:border-blue-500/50 dark:hover:bg-zinc-900">
                <div class="flex items-start justify-between mb-4">
                    <div class="glow-tile !w-12 !h-12 !rounded-xl border border-blue-500/30 shrink-0">
                        <svg class="w-6 h-6 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <span class="font-mono text-[10px] text-blue-600 dark:text-blue-400 bg-blue-500/10 border border-blue-500/20 px-2 py-0.5 rounded-full">
                        Anggota 1
                    </span>
                </div>

                <div class="space-y-1 mb-2">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors">Farrel Hasudungan Immanuel Limbong</h3>
                    <p class="text-xs font-mono text-zinc-500 dark:text-zinc-400">NRP: 5025241016</p>
                </div>
            </div>

            <!-- Member 2 -->
            <div class="group relative overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 p-6 shadow-xs dark:shadow-none transition-all duration-200 hover:border-purple-500/50 dark:hover:bg-zinc-900">
                <div class="flex items-start justify-between mb-4">
                    <div class="glow-tile !w-12 !h-12 !rounded-xl border border-purple-500/30 shrink-0" style="--glow-color: #a855f7">
                        <svg class="w-6 h-6 text-purple-500 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <span class="font-mono text-[10px] text-purple-600 dark:text-purple-400 bg-purple-500/10 border border-purple-500/20 px-2 py-0.5 rounded-full">
                        Anggota 2
                    </span>
                </div>

                <div class="space-y-1 mb-2">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white group-hover:text-purple-500 dark:group-hover:text-purple-400 transition-colors">Muhammad Nabil Fauzan</h3>
                    <p class="text-xs font-mono text-zinc-500 dark:text-zinc-400">NRP: 5025241024</p>
                </div>
            </div>

            <!-- Member 3 -->
            <div class="group relative overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 p-6 shadow-xs dark:shadow-none transition-all duration-200 hover:border-emerald-500/50 dark:hover:bg-zinc-900">
                <div class="flex items-start justify-between mb-4">
                    <div class="glow-tile !w-12 !h-12 !rounded-xl border border-emerald-500/30 shrink-0" style="--glow-color: #10b981">
                        <svg class="w-6 h-6 text-emerald-500 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <span class="font-mono text-[10px] text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-full">
                        Anggota 3
                    </span>
                </div>

                <div class="space-y-1 mb-2">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white group-hover:text-emerald-500 dark:group-hover:text-emerald-400 transition-colors">Athar Bakhtiar Aziz</h3>
                    <p class="text-xs font-mono text-zinc-500 dark:text-zinc-400">NRP: 5025241136</p>
                </div>
            </div>

            <!-- Member 4 -->
            <div class="group relative overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 p-6 shadow-xs dark:shadow-none transition-all duration-200 hover:border-cyan-500/50 dark:hover:bg-zinc-900">
                <div class="flex items-start justify-between mb-4">
                    <div class="glow-tile !w-12 !h-12 !rounded-xl border border-cyan-500/30 shrink-0" style="--glow-color: #06b6d4">
                        <svg class="w-6 h-6 text-cyan-500 dark:text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <span class="font-mono text-[10px] text-cyan-600 dark:text-cyan-400 bg-cyan-500/10 border border-cyan-500/20 px-2 py-0.5 rounded-full">
                        Anggota 4
                    </span>
                </div>

                <div class="space-y-1 mb-2">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white group-hover:text-cyan-500 dark:group-hover:text-cyan-400 transition-colors">Anggota 4</h3>
                    <p class="text-xs font-mono text-zinc-500 dark:text-zinc-400">NRP: -</p>
                </div>
            </div>

            <!-- Member 5 -->
            <div class="group relative overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 p-6 shadow-xs dark:shadow-none transition-all duration-200 hover:border-amber-500/50 dark:hover:bg-zinc-900">
                <div class="flex items-start justify-between mb-4">
                    <div class="glow-tile !w-12 !h-12 !rounded-xl border border-amber-500/30 shrink-0" style="--glow-color: #f59e0b">
                        <svg class="w-6 h-6 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <span class="font-mono text-[10px] text-amber-600 dark:text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-full">
                        Anggota 5
                    </span>
                </div>

                <div class="space-y-1 mb-2">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white group-hover:text-amber-500 dark:group-hover:text-amber-400 transition-colors">Anggota 5</h3>
                    <p class="text-xs font-mono text-zinc-500 dark:text-zinc-400">NRP: -</p>
                </div>
            </div>

            <!-- Member 6 -->
            <div class="group relative overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/70 p-6 shadow-xs dark:shadow-none transition-all duration-200 hover:border-rose-500/50 dark:hover:bg-zinc-900">
                <div class="flex items-start justify-between mb-4">
                    <div class="glow-tile !w-12 !h-12 !rounded-xl border border-rose-500/30 shrink-0" style="--glow-color: #f43f5e">
                        <svg class="w-6 h-6 text-rose-500 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <span class="font-mono text-[10px] text-rose-600 dark:text-rose-400 bg-rose-500/10 border border-rose-500/20 px-2 py-0.5 rounded-full">
                        Anggota 6
                    </span>
                </div>

                <div class="space-y-1 mb-2">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white group-hover:text-rose-500 dark:group-hover:text-rose-400 transition-colors">Anggota 6</h3>
                    <p class="text-xs font-mono text-zinc-500 dark:text-zinc-400">NRP: -</p>
                </div>
            </div>

        </div>
    </section>
</div>
@endsection
