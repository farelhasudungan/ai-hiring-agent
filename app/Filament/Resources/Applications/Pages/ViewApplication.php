<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\Actions\ArchiveApplicationAction;
use App\Filament\Resources\Applications\Actions\CancelProcessingAction;
use App\Filament\Resources\Applications\Actions\ProcessApplicationAction;
use App\Filament\Resources\Applications\ApplicationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewApplication extends ViewRecord
{
    protected static string $resource = ApplicationResource::class;

    public function getTitle(): string
    {
        return (string) $this->getRecordTitle();
    }

    public function getBreadcrumbs(): array
    {
        return [...$this->getResourceBreadcrumbs(), $this->getRecordTitle()];
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            ProcessApplicationAction::make(),
            CancelProcessingAction::make(),
            ArchiveApplicationAction::make(),
        ];
    }
}
