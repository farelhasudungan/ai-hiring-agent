<?php

namespace App\Filament\Resources\Jobs\RelationManagers;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Jobs\JobResource;
use App\Filament\Resources\Jobs\Pages\ViewJob;
use App\Models\Application;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ApplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'applications';

    protected static ?string $title = 'Lamaran';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $pageClass === ViewJob::class && JobResource::canView($ownerRecord);
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        $statuses = Application::statusOptions();

        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->forCompany(Filament::auth()->user()?->company_id)
                ->with(['candidate', 'job']))
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('candidate.full_name')->label('Nama kandidat')->searchable()->sortable(),
                TextColumn::make('candidate.email')->label('Email')->searchable(),
                TextColumn::make('candidate.phone')->label('Telepon')->placeholder('—'),
                TextColumn::make('status')->label('Status')
                    ->formatStateUsing(fn (string $state): string => $statuses[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('source')->label('Sumber'),
                TextColumn::make('submitted_at')->label('Tanggal melamar')->dateTime('d M Y H:i')->sortable()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options($statuses),
            ])
            ->recordActions([
                ViewAction::make()->url(fn (Application $record): string => ApplicationResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('Belum ada lamaran')
            ->emptyStateDescription('Lamaran untuk lowongan ini akan muncul di sini.');
    }
}
