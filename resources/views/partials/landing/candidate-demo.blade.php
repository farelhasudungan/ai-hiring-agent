@php
    $candidates = [
        [
            'initials' => 'AN', 'name' => 'Alya N.', 'role' => 'Product Designer · 4 tahun', 'score' => 94,
            'reason' => 'Riset pengguna dan design system sesuai kebutuhan posisi.',
            'summary' => 'Pengalaman desain produk end-to-end, riset pengguna, dan design system selaras dengan kebutuhan posisi. Gali lebih lanjut dampak keputusan desainnya saat interview.',
            'status' => 'Direkomendasikan untuk ditinjau HR',
            'criteria' => [
                ['score' => 96, 'reason' => 'Figma, prototyping, dan pengembangan design system terlihat dalam portofolio.'],
                ['score' => 92, 'reason' => 'Empat tahun merancang produk digital dari penemuan masalah hingga handoff.'],
                ['score' => 92, 'reason' => 'Terbiasa melakukan wawancara pengguna dan berkolaborasi dengan engineer.'],
            ],
        ],
        [
            'initials' => 'RP', 'name' => 'Raka P.', 'role' => 'UI/UX Designer · 3 tahun', 'score' => 88,
            'reason' => 'Prototyping kuat; kedalaman riset masih perlu digali.',
            'summary' => 'Kuat dalam prototyping dan desain antarmuka. Kolaborasi dengan tim engineering relevan untuk posisi ini; pengalaman memimpin riset pengguna perlu digali lebih lanjut.',
            'status' => 'Direkomendasikan untuk ditinjau HR',
            'criteria' => [
                ['score' => 90, 'reason' => 'Prototyping interaktif dan desain antarmuka menonjol dalam portofolio.'],
                ['score' => 88, 'reason' => 'Tiga tahun sebagai UI/UX Designer dengan pengalaman delivery produk.'],
                ['score' => 83, 'reason' => 'Kolaborasi engineering relevan; kepemilikan riset pengguna belum tergambar jelas.'],
            ],
        ],
        [
            'initials' => 'SK', 'name' => 'Sinta K.', 'role' => 'Visual Designer · 2 tahun', 'score' => 76,
            'reason' => 'Visual kuat; pengalaman produk dan riset perlu ditinjau.',
            'summary' => 'Portofolio visual kuat, terutama dalam konsistensi identitas dan detail antarmuka. Pengalaman produk end-to-end, riset pengguna, dan pengelolaan design system masih perlu ditinjau.',
            'status' => 'Butuh peninjauan pengalaman produk',
            'criteria' => [
                ['score' => 90, 'reason' => 'Kualitas visual dan konsistensi antarmuka kuat dalam contoh pekerjaan.'],
                ['score' => 60, 'reason' => 'Dua tahun berfokus pada desain visual, belum banyak bukti proses produk end-to-end.'],
                ['score' => 65, 'reason' => 'Pengalaman riset pengguna dan kolaborasi lintas fungsi perlu dikonfirmasi.'],
            ],
        ],
    ];
    $criteriaLabels = ['Keahlian desain', 'Pengalaman relevan', 'Riset & kolaborasi'];
    $weights = [50, 30, 20];
@endphp

<section class="bg-fog py-20 lg:py-28" id="demo" aria-labelledby="demo-heading" data-candidate-demo>
    <div class="page-width">
        <div class="mb-12 flex flex-col justify-between gap-6 lg:flex-row lg:items-end" data-reveal>
            <div>
                <p class="eyebrow mb-5">COBA SENDIRI, TANPA AKUN</p>
                <h2 class="section-title" id="demo-heading">Kenali kandidatnya.<br><em>Pahami alasannya.</em></h2>
            </div>
            <p class="max-w-sm text-base leading-7 text-body">Satu posisi, tiga profil, pertimbangan yang terbuka. Pilih kandidat untuk melihat bagaimana rekomendasi dijelaskan.</p>
        </div>

        <div class="artifact overflow-hidden" data-reveal>
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-hairline px-6 py-5 md:px-8">
                <div class="flex items-center gap-3"><span class="icon-bubble"><x-roku-icon name="briefcase" size="18" /></span><div><h3 class="text-sm font-medium">Product Designer</h3><p class="mt-1 text-xs text-muted">Design · Full-time · Remote</p></div></div>
                <span class="rounded-full border border-hairline px-3 py-1.5 text-[11px] text-muted">Demo interaktif · Data ilustrasi</span>
            </div>

            <div class="grid lg:grid-cols-[0.9fr_1.1fr]">
                <div class="border-b border-hairline p-4 sm:p-6 lg:border-b-0 lg:border-r lg:p-8">
                    <div class="mb-5 flex items-center justify-between gap-3"><h3 class="text-sm font-medium">Ranking Kandidat</h3><span class="text-xs text-muted">3 profil</span></div>
                    <p class="mb-5 text-xs leading-5 text-muted">Skor kecocokan dengan kriteria lowongan. Urutan awal adalah rekomendasi AI.</p>
                    <ol class="space-y-3" data-candidate-list aria-label="Pilih kandidat untuk melihat alasan penilaian">
                        @foreach ($candidates as $candidate)
                            <li>
                                <button type="button" class="candidate-row {{ $loop->first ? 'is-selected' : '' }}" data-candidate data-name="{{ $candidate['name'] }}" data-role="{{ $candidate['role'] }}" data-score="{{ $candidate['score'] }}" data-summary="{{ $candidate['summary'] }}" data-status="{{ $candidate['status'] }}" data-criteria="{{ json_encode($candidate['criteria']) }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" aria-controls="candidate-detail">
                                    <span class="pt-3 text-[10px] text-muted" data-candidate-rank aria-hidden="true">0{{ $loop->iteration }}</span>
                                    <span class="candidate-avatar hidden sm:flex" aria-hidden="true">{{ $candidate['initials'] }}</span>
                                    <span class="min-w-0 flex-1"><span class="block text-sm font-medium">{{ $candidate['name'] }}</span><span class="mt-1 block text-[11px] text-muted">{{ $candidate['role'] }}</span><span class="mt-2 block text-xs leading-5 text-body">{{ $candidate['reason'] }}</span></span>
                                    <span class="pt-0.5 text-lg font-medium">{{ $candidate['score'] }}<span class="text-[10px] font-normal text-muted">%</span></span>
                                </button>
                            </li>
                        @endforeach
                    </ol>
                    <div class="mt-8 rounded-2xl bg-fog p-4"><p class="flex items-center gap-2 text-xs font-medium"><x-roku-icon name="sliders" size="14" /> Acuan penilaian demo</p><p class="mt-2 text-xs leading-6 text-body">Keahlian desain 50% · Pengalaman 30% · Riset & kolaborasi 20%. Semua profil memakai bobot yang sama.</p></div>
                    <p class="mt-4 text-[11px] leading-5 text-muted">Semua profil dan skor bersifat ilustratif.</p>
                </div>

                <div class="p-6 sm:p-8" id="candidate-detail" role="region" aria-label="Alasan penilaian kandidat">
                    <div class="flex items-center gap-2 text-xs text-muted"><x-roku-icon name="sparkles" size="16" /> Ringkasan kecocokan AI</div>
                    <div class="mt-6 flex items-center justify-between gap-4">
                        <div><h3 class="text-2xl font-[450] tracking-tight" data-candidate-name>{{ $candidates[0]['name'] }}</h3><p class="mt-2 text-xs text-muted" data-candidate-role>{{ $candidates[0]['role'] }}</p></div>
                        <div class="relative flex size-18 shrink-0 items-center justify-center">
                            <svg class="absolute inset-0 size-full -rotate-90" viewBox="0 0 80 80" fill="none" aria-hidden="true"><circle cx="40" cy="40" r="35" stroke="var(--color-hairline)" stroke-width="3"/><circle cx="40" cy="40" r="35" pathLength="100" stroke="currentColor" stroke-width="3" stroke-dasharray="100" stroke-dashoffset="{{ 100 - $candidates[0]['score'] }}" stroke-linecap="round" data-candidate-ring /></svg>
                            <span class="text-xl font-medium"><span data-candidate-score>{{ $candidates[0]['score'] }}</span><span class="text-[10px] font-normal text-muted">%</span></span>
                        </div>
                    </div>
                    <p class="mt-5 text-sm leading-7 text-body" data-candidate-summary>{{ $candidates[0]['summary'] }}</p>

                    <div class="mt-7 space-y-5">
                        @foreach ($criteriaLabels as $index => $label)
                            <div data-criterion>
                                <div class="mb-2 flex justify-between gap-3 text-xs"><p>{{ $label }} <span class="text-muted">· {{ $weights[$index] }}%</span></p><span data-criterion-score>{{ $candidates[0]['criteria'][$index]['score'] }}/100</span></div>
                                <div class="score-track" role="progressbar" aria-label="{{ $label }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $candidates[0]['criteria'][$index]['score'] }}"><div class="score-fill" style="--score: {{ $candidates[0]['criteria'][$index]['score'] / 100 }}"></div></div>
                                <p class="mt-2 text-[11px] leading-5 text-muted" data-criterion-reason>{{ $candidates[0]['criteria'][$index]['reason'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-7 border-t border-hairline pt-5">
                        <p class="text-xs font-[450]" data-candidate-status>{{ $candidates[0]['status'] }}</p>
                        <div class="mt-4 flex flex-wrap items-center gap-3" data-demo-actions hidden>
                            <button type="button" class="button-primary min-h-11 !px-4 !text-xs" data-prioritize><x-roku-icon name="arrow-up" size="14" /> Jadikan prioritas</button>
                            <button type="button" class="button-ghost min-h-11 !px-4 !text-xs" data-demo-reset>Reset demo</button>
                        </div>
                        <p class="mt-3 text-[11px] leading-5 text-muted" data-demo-feedback role="status" aria-live="polite" aria-atomic="true">AI merekomendasikan. Anda menentukan urutan prioritas.</p>
                    </div>
                </div>
            </div>
        </div>
        <p class="mt-6 text-center text-xs leading-6 text-muted">Klik profil untuk membaca alasan, lalu coba tentukan prioritas Anda. Tidak ada data lamaran asli.</p>
    </div>
</section>
