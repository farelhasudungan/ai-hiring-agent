<?php

namespace App\Filament\Resources\Jobs\Pages;

use App\Filament\Resources\Jobs\JobResource;
use App\Models\Job;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJob extends EditRecord
{
    protected static string $resource = JobResource::class;

    protected static ?string $title = 'Edit Lowongan';

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['company_id'] = $this->getRecord()->company_id;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publicApplication')->label('Halaman lamaran')
                ->url(fn (Job $record): string => route('jobs.apply', $record))
                ->openUrlInNewTab()
                ->visible(fn (Job $record): bool => $record->status === 'open'),
            DeleteAction::make(),
        ];
    }
}
