<?php

namespace App\Filament\Resources\Applications\Schemas;

use App\Models\Application;
use App\Models\ApplicationDocument;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Data kandidat')->columns(2)->schema([
                    TextEntry::make('candidate.full_name')->label('Nama kandidat'),
                    TextEntry::make('candidate.email')->label('Email'),
                    TextEntry::make('candidate.phone')->label('Telepon')->placeholder('—'),
                    TextEntry::make('candidate.linkedin_url')->label('LinkedIn')->placeholder('—'),
                    TextEntry::make('candidate.portfolio_url')->label('Portofolio')->placeholder('—'),
                ]),
                Section::make('Lamaran')->columns(2)->schema([
                    TextEntry::make('job.title')->label('Lowongan'),
                    TextEntry::make('source')->label('Sumber')
                        ->formatStateUsing(fn (string $state): string => Application::sourceOptions()[$state] ?? $state),
                    TextEntry::make('status')->label('Status')
                        ->formatStateUsing(fn (string $state): string => Application::statusOptions()[$state] ?? $state),
                    TextEntry::make('submitted_at')->label('Tanggal melamar')->dateTime('d M Y H:i'),
                    TextEntry::make('latest_match_score')->label('Skor')->placeholder('Belum dinilai'),
                    TextEntry::make('reviewer.name')->label('Direview oleh')->placeholder('—'),
                    TextEntry::make('reviewed_at')->label('Terakhir diubah statusnya')->dateTime('d M Y H:i')->placeholder('—'),
                    TextEntry::make('notes')->label('Catatan')->placeholder('Belum ada catatan')->columnSpanFull(),
                ]),
                Section::make('Dokumen')->schema([
                    RepeatableEntry::make('documents')->hiddenLabel()->placeholder('Belum ada dokumen')->columns(3)->schema([
                        TextEntry::make('file_name')->label('Nama dokumen'),
                        TextEntry::make('download')->label('Download')->state('Unduh dokumen')
                            ->url(fn (ApplicationDocument $record): string => route('filament.admin.application-documents.show', [
                                'application' => $record->application_id, 'document' => $record->id,
                            ])),
                        TextEntry::make('preview')->label('Preview')->state('Lihat PDF')->openUrlInNewTab()
                            ->visible(fn (ApplicationDocument $record): bool => $record->mime_type === 'application/pdf')
                            ->url(fn (ApplicationDocument $record): string => route('filament.admin.application-documents.show', [
                                'application' => $record->application_id, 'document' => $record->id, 'preview' => 1,
                            ])),
                    ]),
                ]),
            ]);
    }
}
