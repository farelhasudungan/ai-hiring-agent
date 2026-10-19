<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Actions\ReviewApplication;
use App\Filament\Resources\Applications\Actions\ArchiveApplicationAction;
use App\Filament\Resources\Applications\ApplicationResource;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditApplication extends EditRecord
{
    protected static string $resource = ApplicationResource::class;

    protected static ?string $title = 'Edit lamaran';

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return Arr::only($data, ['status', 'notes']);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(ReviewApplication::class)->handle($record, Filament::auth()->user(), $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            ArchiveApplicationAction::make()
                ->after(fn () => $this->refreshFormData(['status', 'notes'])),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl('view');
    }
}
