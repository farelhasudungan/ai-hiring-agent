<?php

namespace App\Services\Intake\Adapters;

use App\Services\Intake\Contracts\IntakeAdapter;
use App\Services\Intake\Data\IntakeBatch;
use App\Services\Intake\Data\NormalizedCandidateDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class PdfIntakeAdapter implements IntakeAdapter
{
    public const MAX_ZIP_ENTRIES = 200;

    public const MAX_PDF_BYTES = 20 * 1024 * 1024;

    public const MAX_TOTAL_BYTES = 200 * 1024 * 1024;

    /**
     * @param  array<int, UploadedFile>  $inputs
     */
    public function collect(array $inputs, int $companyId): IntakeBatch
    {
        $documents = [];
        $rejected = [];
        $totalBytes = 0;

        foreach ($inputs as $file) {
            $entries = $this->isZip($file)
                ? $this->readZip($file, $rejected)
                : [[$file->getClientOriginalName(), null, (string) file_get_contents($file->getRealPath())]];

            foreach ($entries as [$name, $originName, $content]) {
                $totalBytes += strlen($content);

                if ($totalBytes > self::MAX_TOTAL_BYTES) {
                    $rejected[] = "{$name}: total upload size limit exceeded";

                    continue;
                }

                if (! $this->looksLikePdf($content) || strlen($content) > self::MAX_PDF_BYTES) {
                    $rejected[] = "{$name}: not a valid PDF or too large";

                    continue;
                }

                $path = "applications/{$companyId}/".Str::uuid().'.pdf';
                Storage::disk(config('filesystems.intake_disk'))->put($path, $content);

                $documents[] = new NormalizedCandidateDocument(
                    fileName: $name,
                    originName: $originName,
                    storedPath: $path,
                    mimeType: 'application/pdf',
                    sizeBytes: strlen($content),
                    contentHash: hash('sha256', $content),
                );
            }
        }

        return new IntakeBatch($documents, $rejected);
    }

    private function isZip(UploadedFile $file): bool
    {
        return strtolower($file->getClientOriginalExtension()) === 'zip';
    }

    private function looksLikePdf(string $content): bool
    {
        return str_starts_with(ltrim(substr($content, 0, 1024)), '%PDF-');
    }

    /**
     * @param  list<string>  $rejected
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function readZip(UploadedFile $file, array &$rejected): array
    {
        $zip = new ZipArchive;

        if ($zip->open($file->getRealPath(), ZipArchive::RDONLY) !== true) {
            $rejected[] = "{$file->getClientOriginalName()}: unreadable zip archive";

            return [];
        }

        $entries = [];

        if ($zip->numFiles > self::MAX_ZIP_ENTRIES) {
            $rejected[] = "{$file->getClientOriginalName()}: more than ".self::MAX_ZIP_ENTRIES.' entries';
            $zip->close();

            return [];
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = (string) $zip->getNameIndex($i);

            if (str_ends_with($entryName, '/') || $this->shouldIgnoreEntry($entryName)) {
                continue;
            }

            if ($this->isUnsafePath($entryName)) {
                $rejected[] = "{$entryName}: unsafe path";

                continue;
            }

            $stream = $zip->getStream($entryName);

            if ($stream === false) {
                $rejected[] = "{$entryName}: unreadable entry";

                continue;
            }

            $content = (string) stream_get_contents($stream, self::MAX_PDF_BYTES + 1);
            fclose($stream);

            $entries[] = [basename($entryName), $entryName, $content];
        }

        $zip->close();

        return $entries;
    }

    private function shouldIgnoreEntry(string $entryName): bool
    {
        return str_starts_with($entryName, '__MACOSX/')
            || str_starts_with(basename($entryName), '.')
            || strtolower(pathinfo($entryName, PATHINFO_EXTENSION)) !== 'pdf';
    }

    private function isUnsafePath(string $entryName): bool
    {
        return str_contains($entryName, '..')
            || str_starts_with($entryName, '/')
            || str_contains($entryName, '\\');
    }
}
