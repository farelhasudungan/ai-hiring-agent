<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ApplicationDocumentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $application, string $document): BinaryFileResponse
    {
        $user = Filament::auth()->user();
        $record = Application::query()->forCompany($user->company_id)->findOrFail($application);
        Gate::forUser($user)->authorize('view', $record);
        $document = $record->documents()->findOrFail($document);

        $disk = Storage::disk('local');
        $directory = "applications/{$record->company_id}/{$record->id}/";
        abort_unless(str_starts_with($document->file_path, $directory), 404);
        $path = realpath($disk->path($document->file_path));
        $root = realpath($disk->path(''));
        abort_unless($path !== false && $root !== false && is_file($path)
            && str_starts_with($path, $root.DIRECTORY_SEPARATOR.$directory), 404);

        $preview = $request->boolean('preview');
        abort_if($preview && (new \finfo(FILEINFO_MIME_TYPE))->file($path) !== 'application/pdf', 415);

        $name = preg_replace('/[\x00-\x1F\x7F]/', '', basename(str_replace('\\', '/', $document->file_name)));
        $response = response()->file($path, [
            'Content-Type' => $preview ? 'application/pdf' : 'application/octet-stream',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "frame-ancestors 'self'",
        ]);

        return $response->setContentDisposition(
            $preview ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $name ?: 'document',
        );
    }
}
