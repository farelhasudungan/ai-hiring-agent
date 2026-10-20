<?php

namespace App\Filament\Resources\Applications\Tables;

use App\Filament\Resources\Applications\Actions\ArchiveApplicationAction;
use App\Filament\Resources\Applications\Actions\CancelProcessingAction;
use App\Filament\Resources\Applications\Actions\ProcessApplicationAction;
use App\Models\Application;
use App\Models\Job;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->poll('10s')
            ->columns([
                TextColumn::make('candidate.full_name')->label('Nama kandidat')->searchable()->sortable(),
                TextColumn::make('job.title')->label('Lowongan')->searchable()->sortable(),
                TextColumn::make('source')->label('Sumber')
                    ->formatStateUsing(fn (string $state): string => Application::sourceOptions()[$state] ?? $state),
                TextColumn::make('status')->label('Status')->sortable()
                    ->formatStateUsing(fn (string $state): string => Application::statusOptions()[$state] ?? $state),
                TextColumn::make('submitted_at')->label('Tanggal melamar')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('latest_match_score')->label('Skor')->numeric()->sortable()->placeholder('Belum dinilai'),
                TextColumn::make('latest_ai_status')->label('Status AI')->badge()->color('gray')->placeholder('Belum diproses')
                    ->formatStateUsing(fn (string $state): string => Application::aiStatusOptions()[$state] ?? $state),
            ])
            ->filters([
                SelectFilter::make('job_id')->label('Lowongan')->searchable()
                    ->options(fn (): array => Job::query()->where('company_id', Filament::auth()->user()?->company_id)
                        ->orderBy('title')->pluck('title', 'id')->all()),
                SelectFilter::make('status')->label('Status')->options(Application::statusOptions()),
                SelectFilter::make('source')->label('Sumber')->options(Application::sourceOptions()),
            ])
            ->recordActions([
                ViewAction::make(),
                ProcessApplicationAction::make(),
                CancelProcessingAction::make(),
                ArchiveApplicationAction::make(),
            ])
            ->emptyStateHeading('Belum ada lamaran')
            ->emptyStateDescription('Lamaran yang masuk ke perusahaan Anda akan muncul di sini.');
    }
}
