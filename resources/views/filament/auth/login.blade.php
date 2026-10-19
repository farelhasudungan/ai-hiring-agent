<x-auth-shell>
    <p class="eyebrow">Selamat datang kembali</p>
    <h1 class="mt-3 font-serif text-heading font-normal">Masuk ke ruang Anda.</h1>
    <p class="mt-3 text-base leading-relaxed text-body">Lanjutkan perjalanan rekrutmen bersama tim Anda.</p>
    <div class="auth-login-form mt-6">
        {{ $this->content }}
    </div>
    <p class="mt-6 text-center text-sm text-muted">Belum punya akun? <a href="{{ route('filament.admin.auth.register') }}" class="text-ink underline-offset-4 hover:underline">Buat akun →</a></p>
</x-auth-shell>
