<?php

namespace App\Filament\Resources\Applications\Actions;

use App\Actions\ReviewApplication;
use App\Models\Application;
use Filament\Actions\Action;
use Filament\Facades\Filament;

class ArchiveApplicationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'archive';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Archive')
            ->color('gray')
            ->authorize('update')
            ->visible(fn (Application $record): bool => $record->status !== 'archived')
            ->requiresConfirmation()
            ->modalHeading('Arsipkan lamaran?')
            ->modalDescription('Lamaran dan dokumennya tetap disimpan. Status akan diubah menjadi diarsipkan.')
            ->modalSubmitActionLabel('Arsipkan')
            ->successNotificationTitle('Lamaran diarsipkan')
            ->action(function (Application $record, ReviewApplication $review): void {
                $review->handle($record, Filament::auth()->user(), ['status' => 'archived']);
                $this->success();
            });
    }
}
