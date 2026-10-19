@props(['code', 'url'])

<x-filament::section heading="Undangan siap dibagikan" description="Bagikan kode atau link secara pribadi. Saat mendaftar, penerima harus menggunakan email yang diundang.">
    <x-filament::fieldset label="Kode undangan">
        <x-filament::input.wrapper>
            <x-filament::input id="invitation-code" type="text" :value="$code" readonly aria-label="Kode undangan" x-on:click="$el.select()" />
            <x-slot name="suffix">
                <button type="button" class="fi-icon-btn fi-size-md" aria-label="Salin kode undangan" title="Salin kode undangan"
                    x-data="filamentCopyable" x-bind="bindings"
                    x-on:click.prevent.stop="copy(document.getElementById('invitation-code').value, 'Kode undangan disalin', 2000, 'Gagal menyalin. Salin kode secara manual.')">
                    <x-roku-icon name="copy" />
                </button>
            </x-slot>
        </x-filament::input.wrapper>
    </x-filament::fieldset>

    <x-filament::fieldset label="Link undangan" class="mt-4">
        <x-filament::input.wrapper>
            <x-filament::input id="invitation-url" type="text" :value="$url" readonly aria-label="Link undangan" x-on:click="$el.select()" />
            <x-slot name="suffix">
                <button type="button" class="fi-icon-btn fi-size-md" aria-label="Salin link undangan" title="Salin link undangan"
                    x-data="filamentCopyable" x-bind="bindings"
                    x-on:click.prevent.stop="copy(document.getElementById('invitation-url').value, 'Link undangan disalin', 2000, 'Gagal menyalin. Salin link secara manual.')">
                    <x-roku-icon name="copy" />
                </button>
            </x-slot>
        </x-filament::input.wrapper>
    </x-filament::fieldset>
</x-filament::section>
