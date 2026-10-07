@extends('layouts.app')
@section('title', 'Profil Kelompok 9')
@section('content')
<section class="page-width pt-16 pb-12 md:pt-24 md:pb-16">
    <p class="eyebrow mb-6">ITS / INFORMATIKA / KELOMPOK 09</p>
    <h1 class="hero-title max-w-4xl">Belajar bersama.<br>Membangun yang <span class="font-serif italic">bermakna.</span></h1>
    <p class="mt-6 max-w-2xl text-lg leading-7 text-body">Enam anggota, satu ruang eksplorasi. Mengenal framework, merancang solusi, dan mengembangkan ide Agentic AI melalui ITS Academic Sandbox.</p>
    <div class="mt-8 flex flex-wrap gap-3"><a class="button-primary" href="{{ route('project') }}">Jelajahi proyek kami <span aria-hidden="true">↗</span></a><a class="button-ghost" href="{{ route('about') }}">Tentang departemen <span aria-hidden="true">→</span></a></div>
    <div class="mt-16 grid gap-6 md:grid-cols-2">
        <div class="overflow-hidden rounded-[4px] border border-hairline bg-surface">
            <div class="terminal-bar"><span>~/its/kelompok-09</span><span aria-hidden="true">— &nbsp; □</span></div>
            <div class="space-y-6 p-6 font-mono text-[13px] leading-6 md:p-8">
                <p><span class="text-muted">$</span> cat team.json</p>
                <div class="border-l border-muted/30 pl-5"><p>{</p><p class="pl-4"><span class="text-muted">"team":</span> "Kelompok 9",</p><p class="pl-4"><span class="text-muted">"members":</span> 6,</p><p class="pl-4"><span class="text-muted">"department":</span> "Informatika ITS",</p><p class="pl-4"><span class="text-muted">"focus":</span> "Framework & Agentic AI"</p><p>}</p></div>
                <p class="text-muted">$ siap belajar, siap membangun<span class="ml-2 inline-block h-4 w-2 bg-body align-middle" aria-hidden="true"></span></p>
            </div>
        </div>
        <div class="overflow-hidden rounded-[4px] border border-hairline bg-surface">
            <div class="terminal-bar"><span>agent / recruiting-hiring</span><span>konsep proyek</span></div>
            <div class="p-6 md:p-8"><p class="eyebrow">01 — RENCANA PROYEK AKHIR</p><h2 class="mt-5 text-2xl leading-8 font-medium tracking-[-0.4px]">Recruiting and<br>Hiring Agents</h2><p class="mt-4 text-sm leading-6 text-body">Email, form screening, dan CV dianalisis AI menjadi ranking kandidat. Hasil diterima atau ditolak dikabarkan melalui email otomatis.</p><a class="mt-8 inline-flex min-h-11 items-center gap-3 text-sm hover:underline" href="{{ route('project') }}">Lihat rancangan agent <span aria-hidden="true">→</span></a></div>
        </div>
    </div>
</section>
<div class="page-width"><div class="flex flex-wrap items-center justify-between gap-6 border-y border-hairline py-6"><span class="eyebrow">DIBANGUN DENGAN</span><span class="text-lg text-body">Laravel</span><span class="text-lg text-body">Tailwind CSS</span><span class="text-lg text-body">Vite</span><span class="font-mono text-sm text-muted">PHP + JavaScript</span></div></div>
<section class="page-width py-16 md:py-24">
    <div class="mb-10 flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow mb-3">ORANG DI BALIK PROYEK</p><h2 class="section-title">Kenali tim kami.</h2></div><span class="eyebrow">06 anggota / Teknik Informatika ITS</span></div>
    @php
        $members = [
            ['Farrel Hasudungan Immanuel Limbong', '5025241016'],
            ['Muhammad Nabil Fauzan', '5025241024'],
            ['Athar Bakhtiar Aziz', '5025241136'],
        ];
    @endphp
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($members as [$name, $nrp])
            <article class="panel flex min-h-48 flex-col"><div class="mb-8 flex items-center justify-between font-mono text-xs text-muted"><span>0{{ $loop->iteration }}</span><span>{{ $nrp === '—' ? 'Profil belum dilengkapi' : 'Anggota tim' }}</span></div><h3 class="max-w-xs text-lg font-medium leading-7">{{ $name }}</h3><p class="mt-auto pt-4 font-mono text-xs text-muted">NRP {{ $nrp }}</p></article>
        @endforeach
    </div>
</section>
@endsection
