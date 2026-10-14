@extends('layouts.app')
@section('title', 'Roku: Dari kotak masuk ke shortlist')

@section('content')
    @include('partials.landing.hero')

    <div class="page-width" data-reveal>
        <div class="flex flex-col items-center justify-between gap-6 border-y border-hairline py-8 lg:flex-row">
            <p class="text-sm text-muted">Untuk tim kecil dengan rencana besar.</p>
            <div class="flex flex-wrap justify-center gap-x-8 gap-y-4 text-sm font-[450] md:gap-x-12">
                <span class="flex items-center gap-2.5"><x-roku-icon name="users" size="18" /> Tim HR & recruiter</span>
                <span class="flex items-center gap-2.5"><x-roku-icon name="briefcase" size="18" /> Founder & hiring manager</span>
                <span class="flex items-center gap-2.5"><x-roku-icon name="layers" size="18" /> Agensi rekrutmen</span>
            </div>
        </div>
    </div>

    <section class="page-width py-20 lg:py-28" aria-labelledby="problem-heading">
        <div class="mx-auto max-w-3xl text-center" data-reveal>
            <p class="eyebrow mb-5">ADA CARA YANG LEBIH RINGAN</p>
            <h2 class="section-title" id="problem-heading">Talenta bagus tak seharusnya<br class="hidden md:block"> <em>tenggelam di inbox.</em></h2>
            <p class="mx-auto mt-5 max-w-xl text-base leading-7 text-body">Merekrut itu tentang mengenal orang. Bukan menghabiskan hari membuka lampiran dan memindahkan isi CV ke spreadsheet.</p>
        </div>
        <div class="mt-12 grid gap-5 md:grid-cols-3" data-reveal-group>
            @foreach ([
                ['mail', '01 / LAMARAN TERSEBAR', 'Satu tempat, bukan puluhan tab.', 'Email Gmail, CV, dan jawaban form dikumpulkan menjadi profil kandidat yang mudah ditelusuri.', 'Dari inbox ke profil kandidat'],
                ['sliders', '02 / SCREENING BERULANG', 'Kriteria yang sama untuk semua.', 'Setiap profil dibandingkan dengan kebutuhan lowongan yang Anda tentukan, dengan acuan penilaian yang konsisten.', 'Dari baca manual ke ringkasan'],
                ['users', '03 / POTENSI TERLEWAT', 'Yang relevan, terlihat lebih dulu.', 'Ranking membantu Anda menemukan kecocokan. Alasannya membantu Anda memahami potensi dan hal yang perlu digali.', 'Dari tumpukan ke prioritas'],
            ] as [$icon, $label, $title, $description, $outcome])
                <article class="flex flex-col rounded-card bg-surface p-7" data-reveal>
                    <x-roku-icon :name="$icon" size="28" class="mb-10" />
                    <p class="text-[11px] tracking-[0.08em] text-muted">{{ $label }}</p>
                    <h3 class="mt-3 text-[22px] font-[450] leading-8 tracking-[-0.01em]">{{ $title }}</h3>
                    <p class="mt-4 text-sm leading-7 text-body">{{ $description }}</p>
                    <p class="mt-auto pt-8 text-xs text-muted">{{ $outcome }} <span aria-hidden="true">↗</span></p>
                </article>
            @endforeach
        </div>
    </section>

    @include('partials.landing.workflow')
    @include('partials.landing.candidate-demo')

    <section class="page-width py-20 lg:py-28" aria-labelledby="human-heading">
        <div class="grid items-center gap-10 rounded-card bg-blush-peach p-8 text-sienna-brown md:grid-cols-[1.4fr_1fr] md:p-12 lg:p-16" data-reveal>
            <div>
                <p class="mb-5 text-xs tracking-[0.12em]">DIBANTU AI. DIPUTUSKAN MANUSIA.</p>
                <h2 class="font-serif text-heading font-normal lg:text-heading-lg" id="human-heading">AI melihat kecocokan.<br><em>Anda melihat potensi.</em></h2>
            </div>
            <div class="md:pl-8">
                <x-roku-icon name="users" size="32" class="mb-6" />
                <p class="text-lg leading-8">Angka adalah awal percakapan, bukan keputusan akhir. Tinjau alasannya, ubah prioritasnya, dan pilih sendiri siapa yang ingin Anda kenal lebih jauh.</p>
                <p class="mt-6 border-t border-sienna-brown/20 pt-5 text-sm leading-6">Roku tidak menerima atau menolak kandidat secara otomatis.</p>
            </div>
        </div>
    </section>

    <section class="page-width grid gap-10 pb-20 lg:grid-cols-[0.85fr_1.15fr] lg:gap-20 lg:pb-28" id="faq" aria-labelledby="faq-heading">
        <div data-reveal>
            <p class="eyebrow mb-5">SEBELUM MULAI</p>
            <h2 class="section-title" id="faq-heading">Sedikit tanya.<br><em>Lebih jelas.</em></h2>
            <p class="mt-5 max-w-xs text-sm leading-7 text-body">Hal-hal yang mungkin ingin Anda ketahui tentang Roku.</p>
        </div>
        <div class="divide-y divide-hairline border-y border-hairline" data-reveal>
            @foreach ([
                ['Untuk siapa Roku dirancang?', 'Untuk tim HR, recruiter, pemilik bisnis, dan agensi rekrutmen kecil yang menerima lamaran lewat Gmail atau form. Roku berfokus pada penyaringan lamaran masuk, bukan mencari kandidat atau menggantikan seluruh sistem rekrutmen.'],
                ['Bagaimana AI menentukan ranking kandidat?', 'Dalam rancangan produk, AI mengekstrak pengalaman, keahlian, dan pendidikan dari CV serta jawaban form, lalu membandingkannya dengan kriteria lowongan Anda. Setiap skor disertai ringkasan kecocokan dan alasan yang bisa ditinjau. Angka dalam demo ini hanya ilustrasi.'],
                ['Apakah AI mengambil keputusan seleksi?', 'Tidak. Ranking adalah rekomendasi awal. Recruiter meninjau informasi, mengubah urutan prioritas bila perlu, dan memutuskan siapa yang dipanggil interview. Tidak ada penerimaan atau penolakan otomatis oleh AI.'],
                ['Apakah Gmail dan Google Form sudah bisa dihubungkan?', 'Belum. Roku saat ini berupa pratinjau produk. Penerimaan lamaran melalui form, analisis CV, dan integrasi Gmail masih dalam pengembangan. Integrasi Google Form juga belum tersedia. Anda dapat mencoba alur peninjauan lewat demo ilustratif di halaman ini.'],
                ['Perlu akun atau CV untuk mencoba demo?', 'Tidak perlu. Semua profil, alasan, dan skor dalam demo adalah data ilustrasi. Pilih kandidat, baca pertimbangan penilaiannya, lalu coba ubah prioritas. Perubahan hanya berlaku pada demo di browser Anda.'],
            ] as [$question, $answer])
                <details class="faq-item py-6">
                    <summary class="flex min-h-6 items-center justify-between gap-5 text-base font-[450] leading-7">
                        {{ $question }}<span class="faq-plus shrink-0 text-2xl font-normal text-muted" aria-hidden="true">+</span>
                    </summary>
                    <p class="mt-4 pr-8 text-sm leading-7 text-body">{{ $answer }}</p>
                </details>
            @endforeach
        </div>
    </section>

    <section class="bg-fog" aria-labelledby="cta-heading">
        <div class="page-width py-20 text-center lg:py-28" data-reveal>
            <p class="eyebrow mb-5">RUANG UNTUK REKRUTMEN YANG LEBIH MANUSIAWI</p>
            <h2 class="section-title" id="cta-heading">Lebih sedikit menyaring.<br><em>Lebih banyak mengenal.</em></h2>
            <p class="mx-auto mt-5 max-w-lg text-base leading-7 text-body">Lihat bagaimana tumpukan lamaran bisa menjadi shortlist yang jelas. Mulai dari satu kandidat.</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a class="button-primary" href="#demo">Coba demo interaktif <span aria-hidden="true">↗</span></a>
                <a class="button-ghost" href="#cara-kerja">Lihat cara kerja</a>
            </div>
            <p class="mt-5 text-xs text-muted">Tanpa akun. Tanpa unggah CV. Langsung jelajahi.</p>
        </div>
    </section>
@endsection
