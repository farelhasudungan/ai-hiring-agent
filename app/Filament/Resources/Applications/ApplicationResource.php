<?php

namespace App\Filament\Resources\Applications;

use App\Filament\Resources\Applications\Pages\EditApplication;
use App\Filament\Resources\Applications\Pages\ListApplications;
use App\Filament\Resources\Applications\Pages\ViewApplication;
use App\Filament\Resources\Applications\Schemas\ApplicationForm;
use App\Filament\Resources\Applications\Schemas\ApplicationInfolist;
use App\Filament\Resources\Applications\Tables\ApplicationsTable;
use App\Models\Application;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?string $modelLabel = 'lamaran';

    protected static ?string $pluralModelLabel = 'Lamaran';

    public static function getRecordTitle(?Model $record): string
    {
        return $record?->candidate?->full_name ?? 'Lamaran';
    }

    public static function hasRecordTitle(): bool
    {
        return true;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forCompany(Filament::auth()->user()?->company_id)
            ->with(['candidate', 'job',
                'currentScreening' => fn (HasOne $query): HasOne => $query
                    ->whereHas('application', fn (Builder $application): Builder => $application->forCompany(Filament::auth()->user()?->company_id)),
                'reviewer' => fn (BelongsTo $query): BelongsTo => $query
                    ->where('company_id', Filament::auth()->user()?->company_id)]);
    }

    public static function form(Schema $schema): Schema
    {
        return ApplicationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ApplicationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApplicationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApplications::route('/'),
            'view' => ViewApplication::route('/{record}'),
            'edit' => EditApplication::route('/{record}/edit'),
        ];
    }
}
