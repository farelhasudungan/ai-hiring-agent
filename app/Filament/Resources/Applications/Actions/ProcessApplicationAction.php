<?php

namespace App\Filament\Resources\Applications\Actions;

use App\Actions\StartApplicationScreening;
use App\Models\Application;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;

class ProcessApplicationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'processAgain';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Proses ulang')->color('gray')->authorize('update')
            ->visible(fn (Application $record): bool => $record->canStartScreening())
            ->requiresConfirmation()->modalHeading('Proses ulang CV dan analisis AI?')
            ->modalDescription('Teks CV akan dikirim ke Google melalui Gemini API. Untuk layanan gratis, gunakan CV dummy tanpa data pribadi, sensitif, atau rahasia. Hasil sebelumnya tetap disimpan; rekomendasi bukan keputusan otomatis.')
            ->schema([
                Checkbox::make('ai_processing_consent')
                    ->label('Saya memastikan kandidat telah menyetujui pemrosesan teks CV melalui Google Gemini sesuai ketentuan layanan yang digunakan.')
                    ->accepted()->required(),
            ])
            ->modalSubmitActionLabel('Proses ulang')
            ->successNotificationTitle('Lamaran dimasukkan ke antrean')
            ->failureNotificationTitle('Antrean tidak tersedia. Silakan coba kembali.')
            ->action(function (Application $record, array $data, StartApplicationScreening $start): void {
                $start->handle($record, Filament::auth()->user(), aiProcessingConsent: $data['ai_processing_consent']);
                $record->latest_ai_status === 'failed' ? $this->failure() : $this->success();
            });
    }
}
