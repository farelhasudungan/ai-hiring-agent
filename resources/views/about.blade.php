@extends('layouts.app')
@section('title', 'About — Tim Kami')
@section('content')
<section class="page-width py-16 md:py-24">
    <p class="eyebrow mb-6">TIM PENGEMBANG / KELOMPOK 09</p>
    <h1 class="hero-title max-w-4xl">Tim di balik<br><span class="font-serif italic">Hiring Agents.</span></h1>
    <p class="mt-6 max-w-3xl text-lg leading-7 text-body">Kami adalah Kelompok 9, mahasiswa Teknik Informatika Institut Teknologi Sepuluh Nopember. Melalui mata kuliah Pemrograman Berbasis Kerangka Kerja, kami merancang Recruiting and Hiring Agents untuk membantu tim HR mengelola proses rekrutmen dengan dukungan Agentic AI.</p>
    <a class="button-primary mt-8" href="{{ route('home') }}">Jelajahi proyek kami <span aria-hidden="true">→</span></a>
    <div class="mt-16 grid gap-6 md:grid-cols-2">
        <article class="panel"><p class="eyebrow">01 / FOKUS KAMI</p><h2 class="mt-5 text-2xl font-medium leading-8 tracking-tight">Rekrutmen yang lebih terstruktur.</h2><p class="mt-4 leading-7 text-body">Menghubungkan email lamaran, jawaban screening, dan CV dalam satu alur: analisis kecocokan, ranking kandidat, hingga pemberitahuan hasil seleksi.</p></article>
        <article class="panel"><p class="eyebrow">02 / PENDEKATAN KAMI</p><h2 class="mt-5 text-2xl font-medium leading-8 tracking-tight">Belajar. Merancang. Berkolaborasi.</h2><p class="mt-4 leading-7 text-body">Mengeksplorasi Laravel dan Agentic AI untuk merancang solusi bagi kebutuhan HR. Website ini memperkenalkan konsep, kapabilitas, serta rancangan arsitektur proyek kami.</p></article>
    </div>
</section>
<section class="page-width pb-16 md:pb-24">
    <div class="mb-10 flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow mb-3">ORANG DI BALIK PROYEK</p><h2 class="section-title">Kenali tim kami.</h2></div><span class="eyebrow">04 anggota / Teknik Informatika ITS</span></div>
    @php
        $members = [
            ['Farrel Hasudungan Immanuel Limbong', '5025241016'],
            ['Muhammad Nabil Fauzan', '5025241024'],
            ['Athar Bakhtiar Aziz', '5025241136'],
            ['Hamzah Ali Abdillah', '5025241023'],
        ];
    @endphp
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        @foreach ($members as [$name, $nrp])
            <article class="panel flex min-h-48 flex-col"><div class="mb-8 flex items-center justify-between font-mono text-xs text-muted"><span>0{{ $loop->iteration }}</span><span>Anggota tim</span></div><h3 class="max-w-xs text-lg font-medium leading-7">{{ $name }}</h3><p class="mt-auto pt-4 font-mono text-xs text-muted">NRP {{ $nrp }}</p></article>
        @endforeach
    </div>
</section>
@endsection
