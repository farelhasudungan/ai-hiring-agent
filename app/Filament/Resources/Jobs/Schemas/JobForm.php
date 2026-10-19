<?php

namespace App\Filament\Resources\Jobs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class JobForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')->label('Judul lowongan')->required()->maxLength(255)->columnSpanFull(),
                TextInput::make('department')->label('Departemen')->maxLength(255),
                TextInput::make('location')->label('Lokasi')->maxLength(255),
                Select::make('status')->label('Status')->required()->default('draft')->options([
                    'draft' => 'Draft',
                    'open' => 'Open',
                    'closed' => 'Closed',
                ]),
                TextInput::make('min_experience_years')->label('Pengalaman minimum (tahun)')
                    ->numeric()->integer()->minValue(0)->maxValue(2147483647),
                Textarea::make('description')->label('Deskripsi')->required()->maxLength(10000)->rows(6)->columnSpanFull(),
                Textarea::make('requirements')->label('Persyaratan')->maxLength(10000)->rows(4)->columnSpanFull(),
                TagsInput::make('required_skills')->label('Keterampilan wajib')->required()
                    ->rules(['array', 'min:1', 'max:50'])
                    ->nestedRecursiveRules(['required', 'string', 'max:100']),
                TagsInput::make('nice_to_have')->label('Keterampilan tambahan')
                    ->rules(['nullable', 'array', 'max:50'])
                    ->nestedRecursiveRules(['required', 'string', 'max:100']),
            ]);
    }
}
