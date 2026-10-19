<?php

namespace App\Filament\Resources\Applications\Schemas;

use App\Models\Application;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Review lamaran')->schema([
                    Select::make('status')->label('Status')->options(Application::statusOptions())->required()
                        ->in(array_keys(Application::statusOptions())),
                    Textarea::make('notes')->label('Catatan')->rows(6)->maxLength(10000),
                ]),
            ]);
    }
}
