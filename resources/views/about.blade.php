@extends('layouts.app')
@section('title', 'Profil Departemen Teknik Informatika ITS')
@section('content')
<section class="page-width py-16 md:py-24">
    <p class="eyebrow mb-6">FTEIC / INSTITUT TEKNOLOGI SEPULUH NOPEMBER</p>
    <h1 class="hero-title">Departemen<br><span class="font-serif italic">Teknik Informatika.</span></h1>
    <p class="mt-6 max-w-3xl text-lg leading-7 text-body">Berdedikasi pada pendidikan, penelitian, dan inovasi dalam ilmu komputer dan rekayasa perangkat lunak. Mempersiapkan mahasiswa untuk membangun teknologi yang bermanfaat bagi masyarakat.</p>
    <a class="button-primary mt-8" href="https://www.its.ac.id/informatika" target="_blank" rel="noreferrer">Kunjungi departemen <span aria-hidden="true">↗</span></a>
    <div class="mt-16 grid gap-6 md:grid-cols-2">
        <article class="panel"><p class="eyebrow">01 / VISI</p><h2 class="mt-6 text-2xl font-medium tracking-tight">Unggul. Inovatif. Berdampak.</h2><p class="mt-4 leading-7 text-body">Menjadi lembaga pendidikan yang unggul dan inovatif-inventif dalam informatika cerdas, mendukung transformasi digital dan berkontribusi kepada masyarakat dengan reputasi internasional.</p></article>
        <article class="panel"><p class="eyebrow">02 / MISI</p><h2 class="mt-6 text-2xl font-medium tracking-tight">Pengetahuan untuk kemajuan.</h2><ul class="mt-4 list-disc space-y-3 pl-5 text-sm leading-6 text-body"><li>Pembelajaran berkualitas dengan standar nasional dan internasional.</li><li>Penelitian yang inovatif, bermutu, dan bermanfaat.</li><li>Pemanfaatan teknologi informasi dan komunikasi untuk masyarakat.</li><li>Kemitraan dengan lembaga di dalam dan luar negeri.</li></ul></article>
    </div>
</section>
<section class="page-width pb-16 md:pb-24">
    <div class="mb-10 flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow mb-3">RUANG EKSPLORASI</p><h2 class="section-title">Delapan fokus. Banyak kemungkinan.</h2></div><span class="eyebrow">Laboratorium departemen</span></div>
    @php
        $labs = [
            ['Rekayasa Perangkat Lunak', 'Analisis kebutuhan, desain sistem, pengujian, dan pengembangan aplikasi modern.'],
            ['Komputasi Cerdas dan Visi', 'AI, machine learning, data mining, dan sistem berbasis pengetahuan.'],
            ['Komputasi Berbasis Jaringan', 'Komunikasi data, administrasi jaringan, keamanan jaringan, dan infrastruktur terdistribusi.'],
            ['Manajemen Cerdas Informasi', 'Perancangan database, optimasi query, data warehouse, dan manajemen data.'],
            ['Grafika, Interaksi, Gim dan Analitik', 'Computer graphics, pengolahan citra, visualisasi, game, dan media interaktif.'],
            ['Pemodelan dan Komputasi Terapan', 'Pemodelan dan simulasi, peramalan sains, optimasi, serta komputasional saintifik.'],
            ['Algoritma dan Pemrograman', 'Struktur data, algoritma, pemrograman kompetitif, dan problem solving.'],
            ['Teknologi Jaringan dan Keamanan Siber Cerdas', 'Keamanan aplikasi, kriptografi, digital forensics, dan perlindungan sistem informasi.'],
        ];
    @endphp
    <div class="border-t border-hairline">
        @foreach ($labs as [$name, $description])
            <article class="grid gap-3 border-b border-hairline py-6 md:grid-cols-[48px_1fr_1fr] md:gap-6"><span class="eyebrow">0{{ $loop->iteration }}</span><h3 class="text-lg font-medium">{{ $name }}</h3><p class="text-sm leading-6 text-body">{{ $description }}</p></article>
        @endforeach
    </div>
</section>
@endsection
