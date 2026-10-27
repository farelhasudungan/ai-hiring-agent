<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationDocumentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $application, string $document): StreamedResponse
    {
        $user = Filament::auth()->user();
        $record = Application::query()->forCompany($user->company_id)->findOrFail($application);
        Gate::forUser($user)->authorize('view', $record);
        $document = $record->documents()->findOrFail($document);

        $disk = Storage::disk();
        $directory = "applications/{$record->company_id}/{$record->id}/";
        abort_unless(str_starts_with($document->file_path, $directory)
            && preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]*\z/', substr($document->file_path, strlen($directory))), 404);

        // Keep local disks and storage fakes safe without resolving remote object keys as paths.
        if ($disk->getAdapter() instanceof LocalFilesystemAdapter) {
            $path = realpath($disk->path($document->file_path));
            $root = realpath($disk->path(''));
            abort_unless($path !== false && $root !== false && is_file($path)
                && str_starts_with($path, $root.DIRECTORY_SEPARATOR.$directory), 404);
        }

        try {
            abort_unless($disk->fileExists($document->file_path), 404);
            $stream = $disk->readStream($document->file_path);
        } catch (FilesystemException $exception) {
            report($exception);
            abort(503, 'Penyimpanan dokumen sedang tidak tersedia. Silakan coba lagi.');
        }

        abort_unless(is_resource($stream), 503);

        $preview = $request->boolean('preview');
        $prefix = '';

        if ($preview) {
            $prefix = fread($stream, 8192);

            if ($prefix === false) {
                fclose($stream);
                abort(503);
            }

            if ((new \finfo(FILEINFO_MIME_TYPE))->buffer($prefix) !== 'application/pdf') {
                fclose($stream);
                abort(415);
            }
        }

        $name = preg_replace('/[\x00-\x1F\x7F]/', '', basename(str_replace('\\', '/', $document->file_name)));

        return response()->streamDownload(function () use ($stream, $prefix): void {
            try {
                echo $prefix;
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, $name ?: 'document', [
            'Content-Type' => $preview ? 'application/pdf' : 'application/octet-stream',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "frame-ancestors 'self'",
        ], $preview ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }
}
