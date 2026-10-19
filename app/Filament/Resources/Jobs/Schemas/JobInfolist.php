<?php

namespace App\Filament\Resources\Jobs\Schemas;

use App\Models\Job;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JobInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Detail Lowongan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('title')->label('Judul lowongan')->columnSpanFull(),
                        TextEntry::make('department')->label('Departemen')->placeholder('—'),
                        TextEntry::make('location')->label('Lokasi')->placeholder('—'),
                        TextEntry::make('status')->label('Status'),
                        TextEntry::make('min_experience_years')->label('Pengalaman minimum (tahun)')->placeholder('—'),
                        TextEntry::make('description')->label('Deskripsi')->columnSpanFull(),
                        TextEntry::make('requirements')->label('Persyaratan')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('required_skills')->label('Keterampilan wajib')->listWithLineBreaks()->bulleted(),
                        TextEntry::make('nice_to_have')->label('Keterampilan tambahan')->listWithLineBreaks()->bulleted()->placeholder('—'),
                        TextEntry::make('public_application_url')->label('Link lamaran publik')
                            ->state(fn (Job $record): string => route('jobs.apply', $record))
                            ->url(fn (Job $record): string => route('jobs.apply', $record))
                            ->openUrlInNewTab()->copyable()->columnSpanFull()
                            ->visible(fn (Job $record): bool => $record->status === 'open'),
                        TextEntry::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
                        TextEntry::make('updated_at')->label('Diperbarui')->dateTime('d M Y H:i'),
                    ]),
            ]);
    }
}
