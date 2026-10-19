@extends('layouts.application')

@section('title', e($job->title.' — '.$job->company->name.' | Roku'))

@section('content')
    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 px-6 py-10 sm:py-16">
        <header class="flex flex-col gap-3">
            <p class="eyebrow">{{ $job->company->name }}</p>
            <h1 class="break-words font-serif text-heading font-normal">{{ $job->title }}</h1>
        </header>

        <section class="min-w-0 rounded-card border border-hairline bg-elevated p-6 sm:p-8" aria-labelledby="application-heading">
            @if (session('application_submitted'))
                <div class="flex flex-col items-start gap-5" role="status">
                    <span class="icon-bubble"><x-roku-icon name="check" /></span>
                    <h2 id="application-heading" class="font-serif text-heading font-normal">Lamaran terkirim.</h2>
                    <p class="leading-relaxed text-body">Terima kasih. Tim {{ $job->company->name }} telah menerima lamaran Anda untuk posisi {{ $job->title }}.</p>
                </div>
            @else
                <h2 id="application-heading" class="font-serif text-heading font-normal">Mari berkenalan.</h2>
                <p class="mt-3 leading-relaxed text-body">Ceritakan tentang diri Anda. Kolom bertanda * wajib diisi.</p>

                @if ($errors->any())
                    <div class="mt-6 rounded-2xl bg-surface p-4 text-sm" role="alert">Lamaran belum terkirim. Periksa kembali kolom yang ditandai. Silakan pilih ulang berkas CV Anda.</div>
                @endif

                <form method="POST" action="{{ route('jobs.applications.store', $job) }}" enctype="multipart/form-data" class="mt-8 flex flex-col gap-5">
                    @csrf
                    <x-application-input name="full_name" label="Nama lengkap" autocomplete="name" required maxlength="255" />
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-application-input name="email" label="Email" type="email" autocomplete="email" required maxlength="255" />
                        <x-application-input name="phone" label="Nomor telepon" type="tel" autocomplete="tel" required maxlength="50" />
                    </div>
                    <x-application-input name="linkedin_url" label="LinkedIn (opsional)" type="url" placeholder="https://linkedin.com/in/..." maxlength="255" />
                    <x-application-input name="portfolio_url" label="Portofolio (opsional)" type="url" placeholder="https://..." maxlength="255" />

                    <div>
                        <label for="application-resume" class="mb-2 block text-sm font-medium">CV / Resume <span aria-hidden="true">*</span></label>
                        <input id="application-resume" name="resume" type="file" required accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                            class="auth-input file:mr-4 file:rounded-full file:border-0 file:bg-surface file:px-4 file:py-2 file:text-sm file:text-ink"
                            aria-describedby="application-resume-help" aria-invalid="{{ $errors->has('resume') ? 'true' : 'false' }}">
                        <div id="application-resume-help" class="mt-2 text-sm text-muted">
                            @error('resume')<p role="alert">{{ $message }}</p>@else<p>PDF atau DOCX, maksimal 5 MB. Berkas disimpan secara privat.</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="application-cover-letter" class="mb-2 block text-sm font-medium">Cover letter (opsional)</label>
                        <textarea id="application-cover-letter" name="cover_letter" rows="6" maxlength="10000" class="auth-input" placeholder="Mengapa Anda tertarik dengan posisi ini?"
                            aria-invalid="{{ $errors->has('cover_letter') ? 'true' : 'false' }}"
                            @error('cover_letter') aria-describedby="application-cover-letter-error" @enderror>{{ is_string(old('cover_letter')) ? old('cover_letter') : '' }}</textarea>
                        @error('cover_letter')<p id="application-cover-letter-error" class="mt-2 text-sm text-body" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="application-consent" class="flex items-start gap-3 text-sm leading-relaxed text-body">
                            <input id="application-consent" name="consent" type="checkbox" value="1" required @checked(old('consent') === '1') class="mt-1 size-4 shrink-0 accent-ink"
                                aria-invalid="{{ $errors->has('consent') ? 'true' : 'false' }}"
                                @error('consent') aria-describedby="application-consent-error" @enderror>
                            <span>Saya menyetujui pemrosesan data pribadi dan dokumen lamaran oleh {{ $job->company->name }} untuk proses rekrutmen posisi ini. *</span>
                        </label>
                        @error('consent')<p id="application-consent-error" class="mt-2 text-sm text-body" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="button-primary w-full">Kirim lamaran <span aria-hidden="true">→</span></button>
                </form>
            @endif
        </section>
    </div>
@endsection
