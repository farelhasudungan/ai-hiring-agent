<section class="border-t border-hairline" id="cara-kerja" aria-labelledby="workflow-heading" data-workflow>
    <div class="page-width grid items-start gap-12 py-20 lg:grid-cols-[1fr_1fr] lg:gap-24 lg:py-28">
        <div class="lg:sticky lg:top-20" data-reveal>
            <p class="eyebrow mb-5">DARI LAMARAN KE LANGKAH BERIKUTNYA</p>
            <h2 class="section-title" id="workflow-heading">Satu alur.<br><em>Lebih ringan.</em></h2>
            <p class="mt-6 max-w-sm text-base leading-7 text-body">Tetap mulai dari tempat lamaran Anda masuk. Roku dirancang untuk mengerjakan bagian yang berulang, supaya Anda bisa fokus pada orangnya.</p>
            <a class="text-link mt-6" href="#demo">Lihat contoh hasilnya <span aria-hidden="true">→</span></a>
            <div class="mt-12 hidden items-center gap-4 text-xs text-muted lg:flex" aria-hidden="true"><span class="h-px w-10 bg-hairline"></span><span data-workflow-caption>01 / Lamaran terkumpul</span></div>
            <p class="mt-5 text-xs leading-5 text-muted">Alur produk yang sedang dikembangkan.</p>
        </div>
        <div class="relative" data-workflow-steps>
            <div class="workflow-track" aria-hidden="true"></div>
            <ol>
                @foreach ([
                    ['Lamaran terkumpul', 'Dari Gmail atau form. Masuk ke satu tempat.', 'Isi email, lampiran CV, dan jawaban form menjadi satu profil. Tak perlu memindahkan data satu per satu.', 'mail', 'Gmail + CV', 'file', 'Form lamaran'],
                    ['Informasi dipahami', 'CV dibaca. Konteksnya dirangkum.', 'AI mengekstrak pengalaman, keahlian, dan pendidikan. Informasi penting jadi lebih mudah ditemukan.', 'file', 'Pengalaman & pendidikan', 'check', 'Keahlian'],
                    ['Kecocokan dinilai', 'Kebutuhan Anda menjadi acuannya.', 'Tentukan kriteria lowongan. AI membandingkan setiap pelamar dengan kebutuhan dan bobot penilaian yang sama.', 'sliders', 'Kriteria lowongan', 'check', 'Bobot penilaian'],
                    ['Ranking dijelaskan', 'Bukan sekadar angka. Ada alasannya.', 'Lihat urutan kandidat, skor kecocokan, dan alasan tiap penilaian, termasuk hal yang masih perlu ditinjau.', 'sparkles', 'Skor + alasan', 'file', 'Ringkasan kecocokan'],
                    ['Anda memutuskan', 'Shortlist dari AI. Keputusan dari Anda.', 'Tinjau profil, ubah urutan prioritas bila perlu, lalu tentukan siapa yang ingin Anda panggil interview.', 'users', 'Tinjauan recruiter', 'check', 'Pilihan Anda'],
                ] as [$stage, $title, $description, $firstIcon, $firstTag, $secondIcon, $secondTag])
                    <li class="workflow-step {{ $loop->first ? 'is-active' : '' }}" data-workflow-step data-stage="0{{ $loop->iteration }} / {{ $stage }}">
                        <span class="workflow-number" aria-hidden="true">0{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1 pb-2" data-reveal>
                            <p class="mb-3 pt-2 text-xs text-muted">{{ $stage }}</p>
                            <h3 class="max-w-sm text-[22px] font-[450] leading-8 tracking-[-0.01em]">{{ $title }}</h3>
                            <p class="mt-3 text-sm leading-7 text-body">{{ $description }}</p>
                            <div class="mt-5 flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-2 rounded-full bg-surface px-3 py-2 text-[11px] text-body"><x-roku-icon :name="$firstIcon" size="14" />{{ $firstTag }}</span>
                                <span class="inline-flex items-center gap-2 rounded-full bg-surface px-3 py-2 text-[11px] text-body"><x-roku-icon :name="$secondIcon" size="14" />{{ $secondTag }}</span>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>
