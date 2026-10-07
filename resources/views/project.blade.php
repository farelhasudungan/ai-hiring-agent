@extends('layouts.app')
@section('title', 'Recruiting and Hiring Agents')
@section('content')
<section class="page-width py-16 md:py-24">
    <p class="eyebrow mb-6">RENCANA PROYEK AKHIR / AGENTIC AI</p>
    <h1 class="hero-title max-w-5xl">Recruiting and<br><span class="font-serif italic">Hiring Agents.</span></h1>
    <p class="mt-6 max-w-3xl text-lg leading-7 text-body">Dari lamaran masuk hingga kabar untuk kandidat. Agent AI membantu HR menganalisis email, jawaban form screening, dan CV, menyusun ranking kandidat, lalu mengirim email diterima atau ditolak secara otomatis.</p>
    <div class="mt-10 flex flex-wrap gap-x-10 gap-y-5 border-y border-hairline py-6 text-sm"><p><span class="eyebrow block mb-2">UNTUK</span>HR & Tim Rekrutmen</p><p><span class="eyebrow block mb-2">SUMBER DATA</span>Email / Form Screening / CV</p><p><span class="eyebrow block mb-2">OUTPUT</span>Ranking & Email Hasil Seleksi</p></div>
    <div class="mt-12 grid gap-6 md:grid-cols-2">
        <article class="panel"><p class="eyebrow">01 / TANTANGAN</p><h2 class="mt-5 text-2xl font-medium leading-8 tracking-tight">Banyak lamaran. Waktu terbatas.</h2><p class="mt-4 leading-7 text-body">HR perlu membaca email, memeriksa CV, membandingkan jawaban screening, dan mengabari kandidat satu per satu. Proses manual ini menyita waktu ketika jumlah pelamar terus bertambah.</p></article>
        <article class="panel"><p class="eyebrow">02 / PENDEKATAN</p><h2 class="mt-5 text-2xl font-medium leading-8 tracking-tight">Analisis. Urutkan. Kabarkan.</h2><p class="mt-4 leading-7 text-body">Agent mengumpulkan data lamaran, menganalisis kecocokan dengan kriteria posisi, dan membuat ranking beserta alasan penilaian. Hasil seleksi diteruskan melalui email otomatis sesuai status kandidat.</p></article>
    </div>
</section>
<section class="page-width pb-16 md:pb-24">
    <p class="eyebrow mb-3">DARI LAMARAN KE HASIL SELEKSI</p><h2 class="section-title mb-10">Satu alur, enam kapabilitas.</h2>
    @php
        $features = [
            ['Pengumpulan Lamaran', 'Menghimpun email lamaran, jawaban form screening, dan lampiran CV ke dalam satu profil kandidat.'],
            ['Ekstraksi Informasi CV', 'Membaca keterampilan, pengalaman, pendidikan, dan informasi relevan dari dokumen kandidat.'],
            ['Analisis Kecocokan AI', 'Membandingkan isi email, jawaban screening, dan CV dengan kebutuhan serta kriteria posisi yang dibuka.'],
            ['Ranking Kandidat', 'Mengurutkan kandidat berdasarkan skor kecocokan, disertai ringkasan dan alasan penilaian.'],
            ['Penentuan Hasil Seleksi', 'Menentukan status diterima atau ditolak berdasarkan skor, ranking, dan aturan seleksi yang ditetapkan HR.'],
            ['Email Hasil Otomatis', 'Mengirim email penerimaan atau penolakan yang dipersonalisasi sesuai hasil seleksi setiap kandidat.'],
        ];
    @endphp
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($features as [$name, $description])
            <article class="panel"><p class="eyebrow mb-8">0{{ $loop->iteration }}</p><h3 class="text-lg font-medium">{{ $name }}</h3><p class="mt-3 text-sm leading-6 text-body">{{ $description }}</p></article>
        @endforeach
    </div>
</section>
<section class="border-y border-hairline">
    <div class="page-width grid gap-12 py-16 md:py-24 lg:grid-cols-2">
        <div><p class="eyebrow mb-3">ALUR KERJA AGENT</p><h2 class="section-title">Lamaran masuk.<br>Kandidat mendapat kepastian.</h2><p class="mt-6 max-w-md leading-7 text-body">Data dari email atau form diproses bersama CV kandidat. AI menganalisis kecocokan dan menyusun ranking, lalu sistem mengirim kabar sesuai hasil seleksi: email penerimaan untuk kandidat yang diterima, atau email penolakan untuk kandidat yang ditolak.</p><p class="mt-6 text-sm text-muted">Ilustrasi konsep proyek, bukan proses seleksi atau pengiriman email langsung.</p></div>
        <div class="overflow-hidden rounded-[4px] border border-hairline bg-surface"><div class="terminal-bar"><span>recruiting-pipeline</span><span>preview</span></div><ol class="divide-y divide-hairline px-6 font-mono text-[13px] leading-6"><li class="py-5"><span class="text-muted">01 / input</span><p class="mt-2">Email / form screening + CV kandidat</p></li><li class="py-5"><span class="text-muted">02 / analysis</span><p class="mt-2">AI membaca data → analisis kecocokan posisi</p></li><li class="py-5"><span class="text-muted">03 / ranking</span><p class="mt-2">Skor + alasan penilaian → urutan kandidat</p></li><li class="py-5"><span class="text-muted">04 / notification</span><p class="mt-2">Diterima → email penerimaan otomatis</p><p class="mt-2">Ditolak → email penolakan otomatis</p></li></ol></div>
    </div>
</section>
<section class="page-width py-16 md:py-24">
    <div class="grid gap-12 lg:grid-cols-2"><div><p class="eyebrow mb-3">RANCANGAN ARSITEKTUR</p><h2 class="section-title">Satu pusat kendali<br>untuk tim rekrutmen.</h2><p class="mt-6 max-w-md leading-7 text-body">HR mengatur kriteria posisi dan aturan seleksi melalui dashboard. Laravel mengelola data kandidat, antrean analisis AI, ranking, serta pengiriman email agar setiap lamaran dapat ditelusuri dari masuk hingga hasil akhir.</p></div>
    <dl class="divide-y divide-hairline border-y border-hairline text-sm">
        @foreach (['Sumber Lamaran' => 'Integrasi email / form web', 'Application Layer' => 'Laravel', 'Dashboard HR' => 'Laravel Livewire', 'Analisis Kandidat' => 'LLM + ekstraksi CV', 'Pemrosesan Asinkron' => 'Laravel Queue', 'Notifikasi Hasil' => 'Laravel Mail / SMTP'] as $layer => $technology)
            <div class="flex flex-wrap justify-between gap-3 py-4"><dt class="text-muted">{{ $layer }}</dt><dd class="font-mono text-[13px]">{{ $technology }}</dd></div>
        @endforeach
    </dl></div>
</section>
@endsection
