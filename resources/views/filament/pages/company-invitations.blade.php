<x-filament-panels::page>
    <x-filament::section heading="Ajak rekan ke perusahaan Anda" description="Undangan berlaku 7 hari, terikat pada email penerima, dan hanya dapat digunakan sekali. Bagikan link secara pribadi; email tidak dikirim otomatis.">
        {{ $this->content }}
    </x-filament::section>
    @if ($invitationUrl)
        <x-filament::section heading="Link undangan siap dibagikan">
            <p>Simpan link ini sekarang dan kirimkan kepada penerima. Token asli tidak disimpan di database.</p>
            <x-filament::input.wrapper class="mt-4">
                <x-filament::input type="text" :value="$invitationUrl" readonly aria-label="Link undangan" x-on:click="$el.select()" />
            </x-filament::input.wrapper>
        </x-filament::section>
    @endif
</x-filament-panels::page>
