<x-filament-panels::page>
    <x-filament::section heading="Ajak rekan ke perusahaan Anda" description="Undangan berlaku 7 hari, terikat pada email penerima, dan hanya dapat digunakan sekali. Bagikan link secara pribadi; email tidak dikirim otomatis.">
        {{ $this->content }}
    </x-filament::section>
    @if ($invitationUrl && $invitationCode)
        <x-company-invitation-result :code="$invitationCode" :url="$invitationUrl" />
    @endif
</x-filament-panels::page>
