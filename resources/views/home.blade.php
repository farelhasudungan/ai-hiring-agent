@extends('layouts.app')
@section('title', 'Hiring Agents — Temukan talenta. Bangun tim terbaik.')
@section('content')
<section class="page-width flex min-h-[calc(100svh-4.5rem)] items-center justify-center py-16 text-center md:py-24" aria-labelledby="hero-heading">
    <div class="mx-auto max-w-4xl">
        <p class="eyebrow mb-6">RECRUITING AND HIRING AGENTS</p>
        <h1 class="hero-title text-balance" id="hero-heading">Temukan talenta tepat.<br><span class="font-serif italic">Tanpa tumpukan pekerjaan.</span></h1>
        <p class="mx-auto mt-6 max-w-2xl text-pretty text-lg leading-7 text-body">AI membantu tim HR menganalisis CV, menyusun shortlist, dan mengabari kandidat. Lebih sedikit pekerjaan berulang, lebih banyak waktu untuk mengenal talenta.</p>
        <a class="button-primary mt-8" href="#demo">Lihat demo kandidat <span aria-hidden="true">↗</span></a>
    </div>
</section>
<div class="border-y border-hairline"><div class="page-width flex flex-wrap items-center justify-between gap-6 py-7"><p class="eyebrow">SATU ALUR. DARI AWAL HINGGA AKHIR.</p><div class="flex flex-wrap gap-x-8 gap-y-3 text-sm text-body"><span>Email & formulir</span><span aria-hidden="true">→</span><span>Analisis CV</span><span aria-hidden="true">→</span><span>Shortlist</span><span aria-hidden="true">→</span><span>Kabar untuk kandidat</span></div></div></div>
<section class="page-width grid items-center gap-10 pt-16 md:pt-24 lg:grid-cols-[0.85fr_1fr]" aria-labelledby="demo-heading">
    <div class="max-w-md">
        <p class="eyebrow mb-4">DARI CV KE SHORTLIST</p>
        <h2 class="section-title" id="demo-heading">Kandidat yang relevan.<br>Alasan yang jelas.</h2>
        <p class="mt-5 leading-7 text-body">Lihat kecocokan kandidat dalam satu tampilan. Pilih profil untuk menjelajahi contoh ringkasan AI dan menentukan siapa yang perlu ditinjau lebih dulu.</p>
        <a class="mt-6 inline-flex min-h-11 items-center gap-3 text-sm hover:underline" href="#cara-kerja">Pelajari cara kerja <span aria-hidden="true">↓</span></a>
    </div>
    <div class="product-preview" id="demo" data-candidate-demo>
        <div class="terminal-bar"><span>hiring / workspace</span><span class="preview-badge">Demo interaktif</span></div>
        <div class="p-5 md:p-7">
            <div class="flex items-start justify-between gap-4"><div><p class="eyebrow mb-2">POSISI TERBUKA</p><h2 class="text-xl font-medium tracking-tight">Product Designer</h2><p class="mt-2 text-sm text-muted">Design · Full-time · Remote</p></div><span class="flex size-10 shrink-0 items-center justify-center rounded-[3px] border border-hairline text-xl" aria-hidden="true">↗</span></div>
            <div class="my-6 grid grid-cols-3 divide-x divide-hairline border-y border-hairline py-5"><div><p class="text-2xl">03</p><p class="mt-1 text-xs text-muted">Kandidat demo</p></div><div class="pl-4"><p class="text-2xl">02</p><p class="mt-1 text-xs text-muted">Shortlist</p></div><div class="pl-4"><p class="text-2xl">01</p><p class="mt-1 text-xs text-muted">Perlu review</p></div></div>
            <div class="mb-4 flex items-center justify-between gap-3"><h3 class="text-sm font-medium">Ranking Kandidat</h3><span class="font-mono text-[11px] text-muted">SKOR KECOCOKAN</span></div>
            <div class="space-y-2" aria-label="Pilih kandidat untuk melihat ringkasan">
                @foreach ([['AN', 'Alya N.', 'Product Designer · 4 tahun', '94', 'Pengalaman desain produk end-to-end, riset pengguna, dan design system selaras dengan kebutuhan posisi.', 'Shortlist'], ['RP', 'Raka P.', 'UI/UX Designer · 3 tahun', '88', 'Kuat dalam prototyping dan desain antarmuka. Pengalaman kolaborasi dengan tim engineering relevan untuk posisi ini.', 'Shortlist'], ['SK', 'Sinta K.', 'Visual Designer · 2 tahun', '76', 'Portofolio visual kuat. Pengalaman riset pengguna dan pengelolaan design system masih perlu ditinjau lebih lanjut.', 'Perlu review']] as [$initials, $name, $role, $score, $summary, $status])
                    <button type="button" class="candidate-row {{ $loop->first ? 'is-selected' : '' }}" data-candidate data-name="{{ $name }}" data-summary="{{ $summary }}" data-status="{{ $status }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                        <span class="candidate-avatar" aria-hidden="true">{{ $initials }}</span><span class="min-w-0 flex-1"><span class="block text-sm font-medium">{{ $name }}</span><span class="mt-1 block text-xs text-muted">{{ $role }}</span></span><span class="font-mono text-sm">{{ $score }}<span class="text-muted">%</span></span>
                    </button>
                @endforeach
            </div>
            <div class="mt-5 border-l-2 border-ink bg-canvas p-4" aria-live="polite" aria-atomic="true"><p class="eyebrow">RINGKASAN AI / <span data-candidate-name>Alya N.</span></p><p class="mt-2 text-sm leading-6 text-body" data-candidate-summary>Pengalaman desain produk end-to-end, riset pengguna, dan design system selaras dengan kebutuhan posisi.</p><p class="mt-3 text-xs font-medium" data-candidate-status>Shortlist</p></div>
            <p class="mt-4 text-xs leading-5 text-muted">Data ilustrasi. Pilih kandidat untuk menjelajahi contoh hasil analisis.</p>
        </div>
    </div>
</section>
<section class="page-width py-16 md:py-24" id="fitur">
    <div class="mb-12 grid gap-6 md:grid-cols-2 md:items-end"><div><p class="eyebrow mb-4">BUAT RUANG UNTUK HAL YANG PENTING</p><h2 class="section-title">Kenali potensinya.<br><span class="font-serif italic font-normal">Bukan hanya isi CV-nya.</span></h2></div><p class="max-w-md text-body leading-7 md:justify-self-end">Kurangi pekerjaan berulang agar tim Anda bisa fokus pada percakapan yang tepat dan membangun hubungan dengan calon anggota tim.</p></div>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach ([['↙', 'Semua lamaran, satu tempat.', 'Satukan email, jawaban screening, dan CV ke dalam profil kandidat yang mudah ditelusuri.', 'PENGUMPULAN LAMARAN'], ['≡', 'Langsung ke informasi penting.', 'Temukan keterampilan, pengalaman, dan pendidikan tanpa membaca ulang setiap dokumen.', 'EKSTRAKSI CV'], ['◎', 'Kecocokan yang punya alasan.', 'Bandingkan profil dengan kebutuhan posisi, lengkap dengan ringkasan alasan penilaian.', 'ANALISIS AI'], ['↗', 'Prioritaskan kandidat potensial.', 'Gunakan ranking kecocokan untuk menyusun shortlist dan menentukan siapa yang perlu ditinjau lebih dulu.', 'RANKING KANDIDAT'], ['⊞', 'Proses seleksi lebih terarah.', 'Gunakan kriteria posisi dan aturan seleksi tim Anda sebagai acuan dalam setiap tahap penilaian.', 'KRITERIA SELEKSI'], ['→', 'Setiap kandidat mendapat kabar.', 'Siapkan email hasil seleksi yang personal agar komunikasi tetap jelas, konsisten, dan tepat sasaran.', 'NOTIFIKASI EMAIL']] as [$icon, $title, $description, $label])
            <article class="feature-card"><span class="feature-icon" aria-hidden="true">{{ $icon }}</span><p class="eyebrow mt-8">{{ $label }}</p><h3 class="mt-3 text-xl font-medium leading-7 tracking-tight">{{ $title }}</h3><p class="mt-4 text-sm leading-6 text-body">{{ $description }}</p></article>
        @endforeach
    </div>
</section>
<section class="border-y border-hairline bg-surface" id="cara-kerja">
    <div class="page-width py-16 md:py-24"><div class="mb-12 flex flex-wrap items-end justify-between gap-6"><div><p class="eyebrow mb-4">CARA KERJA</p><h2 class="section-title">Dari inbox yang penuh<br>ke langkah yang jelas.</h2></div><a class="button-ghost" href="#demo">Lihat contoh hasil <span aria-hidden="true">↗</span></a></div>
        <ol class="grid gap-8 md:grid-cols-3">
            @foreach ([['Tentukan kebutuhan tim.', 'Mulai dari posisi, keterampilan, dan kriteria kandidat yang Anda cari. Beri setiap penilaian arah yang jelas.'], ['Biarkan agent merangkum.', 'Data lamaran dianalisis menjadi profil, skor kecocokan, dan alasan penilaian yang mudah dibandingkan.'], ['Ambil langkah berikutnya.', 'Tinjau shortlist, tentukan hasil seleksi, lalu teruskan kabar kepada kandidat melalui email.']] as [$title, $description])
                <li class="border-t border-hairline pt-6"><span class="font-mono text-sm text-muted">0{{ $loop->iteration }} /</span><h3 class="mt-8 text-xl font-medium">{{ $title }}</h3><p class="mt-4 text-sm leading-6 text-body">{{ $description }}</p></li>
            @endforeach
        </ol>
    </div>
</section>
<section class="page-width grid gap-10 py-16 md:py-24 lg:grid-cols-[1fr_1.2fr]">
    <div><p class="eyebrow mb-4">SEBELUM ANDA MULAI</p><h2 class="section-title">Pertanyaan sederhana.<br>Jawaban yang jelas.</h2></div>
    <div class="divide-y divide-hairline border-y border-hairline">
        @foreach ([['Untuk siapa Hiring Agents dirancang?', 'Untuk recruiter, tim HR, dan hiring manager yang ingin mengelola lamaran serta membandingkan kandidat dalam alur kerja yang lebih terstruktur.'], ['Apa saja yang dianalisis oleh agent?', 'Informasi dari email lamaran, jawaban form screening, dan CV dibandingkan dengan kriteria posisi, lalu dirangkum menjadi profil dan ranking kecocokan.'], ['Apakah saya bisa mencoba tanpa mengunggah CV?', 'Ya. Demo interaktif di halaman ini menggunakan data ilustrasi. Klik kandidat untuk melihat contoh ringkasan kecocokan tanpa akun atau unggahan dokumen.'], ['Apakah layanan rekrutmen sudah tersedia?', 'Saat ini Anda dapat menjelajahi demo pengalaman produk. Integrasi lamaran, analisis CV langsung, dan pengiriman email masih dalam pengembangan.']] as [$question, $answer])
            <details class="faq-item py-6"><summary class="flex cursor-pointer items-center justify-between gap-4 text-sm font-medium">{{ $question }}<span class="faq-plus text-xl text-muted" aria-hidden="true">+</span></summary><p class="mt-4 pr-8 text-sm leading-7 text-body">{{ $answer }}</p></details>
        @endforeach
    </div>
</section>
<section class="border-t border-hairline"><div class="page-width py-16 text-center md:py-24"><p class="eyebrow mb-5">YOUR NEXT GREAT HIRE STARTS HERE</p><h2 class="hero-title">Tim hebat dimulai dari<br><span class="font-serif italic">kecocokan yang tepat.</span></h2><p class="mx-auto mt-6 max-w-lg leading-7 text-body">Lihat bagaimana Hiring Agents membantu mengubah informasi kandidat menjadi langkah rekrutmen yang lebih jelas.</p><a class="button-primary mt-8" href="#demo">Jelajahi demo kandidat <span aria-hidden="true">↗</span></a></div></section>
@endsection
