<?php

namespace App\Filament\Resources\Applications\Schemas;

use App\Models\Application;
use App\Models\ApplicationDocument;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
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
                        TextEntry::make('parsing_status')->label('Status parsing')
                            ->formatStateUsing(fn (string $state): string => ['pending' => 'Menunggu', 'success' => 'Selesai', 'failed' => 'Gagal', 'cancelled' => 'Dibatalkan'][$state] ?? $state),
                        TextEntry::make('error_message')->label('Kendala parsing')->placeholder('—'),
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
                Section::make('Hasil analisis AI')->columns(2)
                    ->extraAttributes(['wire:poll.10s' => '$refresh'])
                    ->schema([
                        Text::make('Penilaian awal berbasis CV, bukan keputusan penerimaan otomatis. HR tetap memverifikasi hasil.')->columnSpanFull(),
                        TextEntry::make('latest_ai_status')->label('Status AI')->badge()->color('gray')->placeholder('Belum diproses')
                            ->formatStateUsing(fn (string $state): string => Application::aiStatusOptions()[$state] ?? $state),
                        TextEntry::make('latest_ai_at')->label('Selesai diproses')->dateTime('d M Y H:i')->placeholder('—'),
                        TextEntry::make('processing_error')->label('Kendala pemrosesan')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('currentScreening.recommendation')->label('Rekomendasi awal')->placeholder('—'),
                        TextEntry::make('currentScreening.model_name')->label('Model AI')->placeholder('—'),
                        TextEntry::make('currentScreening.summary')->label('Ringkasan')->placeholder('Belum ada hasil')->columnSpanFull(),
                        TextEntry::make('currentScreening.matched_skills')->label('Skill wajib cocok')->listWithLineBreaks()->bulleted()->placeholder('—'),
                        TextEntry::make('currentScreening.missing_skills')->label('Skill wajib belum ditemukan')->listWithLineBreaks()->bulleted()->placeholder('—'),
                        TextEntry::make('currentScreening.experience_years')->label('Pengalaman relevan (tahun penuh)')->placeholder('Tidak diketahui'),
                        TextEntry::make('currentScreening.education_level')->label('Pendidikan (tidak dihitung dalam skor)')->placeholder('Tidak diketahui'),
                        TextEntry::make('currentScreening.risk_flags')->label('Hal yang perlu diverifikasi')->listWithLineBreaks()->bulleted()->placeholder('—')->columnSpanFull(),
                        TextEntry::make('currentScreening.score_breakdown.components.required.points')->label('Poin skill wajib')->numeric(2)->placeholder('—'),
                        TextEntry::make('currentScreening.score_breakdown.components.required.weight')->label('Bobot skill wajib (%)')->numeric(2)->placeholder('—'),
                        TextEntry::make('currentScreening.score_breakdown.components.experience.points')->label('Poin pengalaman')->numeric(2)->placeholder('—'),
                        TextEntry::make('currentScreening.score_breakdown.components.experience.weight')->label('Bobot pengalaman (%)')->numeric(2)->placeholder('—'),
                        TextEntry::make('currentScreening.score_breakdown.components.nice.points')->label('Poin nice-to-have')->numeric(2)->placeholder('—'),
                        TextEntry::make('currentScreening.score_breakdown.components.nice.weight')->label('Bobot nice-to-have (%)')->numeric(2)->placeholder('—'),
                        TextEntry::make('currentScreening.score_breakdown.version')->label('Versi rumus')->placeholder('—'),
                    ]),
                Section::make('Riwayat pemrosesan AI')->collapsed()->schema([
                    RepeatableEntry::make('aiScreenings')->hiddenLabel()->placeholder('Belum ada riwayat')->columns(3)->schema([
                        TextEntry::make('created_at')->label('Dimulai')->dateTime('d M Y H:i'),
                        TextEntry::make('status')->label('Status')
                            ->formatStateUsing(fn (string $state): string => Application::aiStatusOptions()[$state] ?? $state),
                        TextEntry::make('match_score')->label('Skor')->placeholder('—'),
                        TextEntry::make('error_message')->label('Kendala')->placeholder('—')->columnSpanFull(),
                    ]),
                ]),
            ]);
    }
}
