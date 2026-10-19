<div class="grid gap-8 lg:min-h-[min(560px,calc(100dvh-112px))] lg:grid-cols-2 lg:gap-12">
    <section id="auth-form" tabindex="-1" class="flex items-center py-6" aria-label="Autentikasi akun">
        <div class="mx-auto w-full max-w-[420px]">
            {{ $slot }}
        </div>
    </section>
    <aside class="auth-intro flex flex-col justify-between gap-6 rounded-card bg-blush-peach p-8 text-sienna-brown lg:p-10" aria-label="Tentang ruang kerja Roku">
        <div>
            <span class="inline-flex items-center gap-2 text-sm"><x-roku-icon name="users" size="18" /> Ruang untuk tim Anda</span>
            <h2 class="mt-6 font-serif text-heading font-normal tracking-[-0.015em]">Tim yang tepat,<br>dimulai <em>dari sini.</em></h2>
            <p class="mt-4 max-w-sm text-sm leading-relaxed opacity-80">Ruang kerja bersama untuk rekrutmen yang terarah. Satu tim, keputusan tetap di tangan Anda.</p>
        </div>
        <div>
            <ul class="space-y-3 text-sm">
                <li class="flex items-center gap-3"><x-roku-icon name="check" size="18" class="shrink-0" /> Akun pribadi untuk setiap anggota</li>
                <li class="flex items-center gap-3"><x-roku-icon name="check" size="18" class="shrink-0" /> Ruang kerja untuk perusahaan Anda</li>
                <li class="flex items-center gap-3"><x-roku-icon name="check" size="18" class="shrink-0" /> Kolaborasi dengan satu tim</li>
            </ul>
            <p class="mt-6 border-t border-current/15 pt-4 text-xs leading-relaxed opacity-70">Integrasi Gmail, analisis CV, dan Google Form masih dalam pengembangan.</p>
        </div>
    </aside>
</div>
