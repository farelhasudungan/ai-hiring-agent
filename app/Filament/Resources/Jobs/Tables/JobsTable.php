<?php

namespace App\Filament\Resources\Jobs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('title')->label('Judul')->searchable()->sortable(),
                TextColumn::make('department')->label('Departemen')->searchable()->placeholder('—'),
                TextColumn::make('location')->label('Lokasi')->searchable()->placeholder('—'),
                TextColumn::make('status')->label('Status'),
                TextColumn::make('applications_count')->label('Lamaran')->counts('applications'),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'open' => 'Open',
                    'closed' => 'Closed',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
