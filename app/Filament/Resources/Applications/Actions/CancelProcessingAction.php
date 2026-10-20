<?php

namespace App\Filament\Resources\Applications\Actions;

use App\Actions\CancelApplicationScreening;
use App\Models\Application;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class CancelProcessingAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'cancelProcessing';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Batalkan proses')->color('gray')->authorize('update')
            ->visible(fn (Application $record): bool => $record->canCancelScreening())
            ->requiresConfirmation()->modalHeading('Batalkan pemrosesan CV dan AI?')
            ->modalDescription('Lamaran dan riwayat tetap disimpan. Status lamaran kembali ke Diterima dan dapat diproses ulang. Request AI yang sudah terkirim mungkin tetap berjalan di gateway, tetapi hasilnya tidak akan digunakan.')
            ->modalSubmitActionLabel('Batalkan proses')
            ->schema([
                Hidden::make('run_id')->required()->rules(['uuid'])
                    ->default(fn (Application $record): ?string => $record->processing_run_id),
            ])
            ->successNotificationTitle('Pemrosesan dibatalkan')
            ->action(function (Application $record, array $data, CancelApplicationScreening $cancel): void {
                try {
                    $cancel->handle($record, Filament::auth()->user(), $data['run_id']);
                } catch (ValidationException $exception) {
                    Notification::make()->warning()->title('Proses tidak dapat dibatalkan')
                        ->body($exception->validator->errors()->first())->send();
                    $this->halt();
                }

                $this->success();
            });
    }
}
