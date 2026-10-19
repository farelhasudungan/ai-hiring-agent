<?php

namespace App\Filament\Resources\Jobs\Pages;

use App\Filament\Resources\Jobs\JobResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateJob extends CreateRecord
{
    protected static string $resource = JobResource::class;

    protected static ?string $title = 'Tambah lowongan';

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $companyId = Filament::auth()->user()?->company_id;
        abort_if($companyId === null, 403);
        $data['company_id'] = $companyId;

        return $data;
    }
}
