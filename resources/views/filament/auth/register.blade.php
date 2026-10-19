<x-auth-shell>
    <div aria-live="polite">
        <h1 class="font-serif text-heading font-normal">{{ match ($step) { 1 => 'Mulai dari email.', 2 => 'Mari berkenalan.', default => 'Ruang untuk tim Anda.' } }}</h1>
        <p class="mt-3 text-base leading-relaxed text-body">{{ match ($step) { 1 => 'Langkah kecil pertama untuk membangun tim yang tepat.', 2 => 'Beri tahu kami nama Anda dan buat password yang aman.', default => 'Buat perusahaan baru atau bergabung dengan tim yang sudah ada.' } }}</p>
    </div>

    @if ($step > 1)
        <p class="mt-4 flex items-center gap-2 break-all text-sm text-body"><x-roku-icon name="mail" size="16" /> {{ $data['email'] }}</p>
    @endif

    <form wire:submit="{{ $step === 3 ? 'register' : 'nextStep' }}" class="mt-6 space-y-5">
        <div wire:key="registration-step-{{ $step }}" x-data x-init="$nextTick(() => $el.querySelector('input:not([type=radio])')?.focus())" class="space-y-5">
            @if ($step === 1)
                <x-auth-input field="email" label="Email" type="email" placeholder="anda@perusahaan.com" autocomplete="email" required maxlength="255" />
            @elseif ($step === 2)
                <x-auth-input field="name" label="Nama lengkap" placeholder="Nama Anda" autocomplete="name" required maxlength="255" />
                <div x-data="{ show: false }">
                    <x-auth-input field="password" label="Password" type="password" x-bind:type="show ? 'text' : 'password'" autocomplete="new-password" required maxlength="255" />
                    <label class="mt-3 inline-flex cursor-pointer items-center gap-2 text-sm text-body"><input type="checkbox" x-model="show" class="size-4 accent-ink"> Tampilkan password</label>
                </div>
            @else
                <fieldset>
                    <legend class="mb-3 text-sm font-medium">Bagaimana Anda ingin memulai?</legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="auth-company-option {{ ($data['company_mode'] ?? '') === 'create' ? 'is-selected' : '' }}">
                            <input type="radio" wire:model.live="data.company_mode" value="create" class="size-4 accent-ink">
                            <span><span class="block text-sm font-medium">Buat perusahaan</span><span class="mt-1 block text-xs leading-relaxed text-muted">Mulai ruang kerja baru</span></span>
                        </label>
                        <label class="auth-company-option {{ ($data['company_mode'] ?? '') === 'join' ? 'is-selected' : '' }}">
                            <input type="radio" wire:model.live="data.company_mode" value="join" class="size-4 accent-ink">
                            <span><span class="block text-sm font-medium">Gabung perusahaan</span><span class="mt-1 block text-xs leading-relaxed text-muted">Saya punya undangan</span></span>
                        </label>
                    </div>
                    @error('data.company_mode')<p class="mt-2 text-sm" role="alert">{{ $message }}</p>@enderror
                </fieldset>
                @if (($data['company_mode'] ?? '') === 'join')
                    <x-auth-input field="invitation" label="Kode atau link undangan" placeholder="Tempel undangan dari admin tim" autocomplete="off" required hint="Gunakan email yang sama dengan penerima undangan. Undangan berlaku selama 7 hari." />
                @else
                    <x-auth-input field="company_name" label="Nama perusahaan" placeholder="Contoh: Studio Nusantara" autocomplete="organization" required maxlength="255" hint="Anda akan menjadi admin perusahaan. Mulai dengan paket Free." />
                @endif
            @endif
        </div>

        @error('attempt')<p class="rounded-2xl bg-surface p-4 text-sm" role="alert">{{ $message }}</p>@enderror

        <div class="flex gap-3">
            @if ($step > 1)
                <button type="button" wire:click="previousStep" wire:loading.attr="disabled" class="button-ghost">Kembali</button>
            @endif
            <button type="submit" wire:loading.attr="disabled" class="button-primary flex-1 disabled:opacity-50">
                <span wire:loading.remove wire:target="nextStep,register">{{ $step === 3 ? 'Buat akun' : 'Lanjut' }} <span class="ml-2" aria-hidden="true">→</span></span>
                <span wire:loading wire:target="nextStep,register" role="status">Memproses…</span>
            </button>
        </div>
    </form>
    <p class="mt-6 text-center text-sm text-muted">Sudah punya akun? <a href="{{ route('filament.admin.auth.login') }}" class="text-ink underline-offset-4 hover:underline">Sign In →</a></p>
</x-auth-shell>
