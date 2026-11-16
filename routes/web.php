<?php

use App\Http\Controllers\Api\ChatAiController;
use App\Http\Controllers\ChatSessionController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'index'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');

Route::get('/jobs/{job}/apply', [JobApplicationController::class, 'create'])->whereNumber('job')->name('jobs.apply');
Route::post('/jobs/{job}/apply', [JobApplicationController::class, 'store'])
    ->whereNumber('job')->middleware('throttle:5,1')->name('jobs.applications.store');

Route::middleware('auth')->prefix('agentic-chat')->name('agentic-chat.')->group(function () {
    // Primary entry point: the interactive Livewire workspace.
    Route::view('/', 'agentic-chat.workspace')->name('workspace');

    // Browser transport for the shared SSE endpoint (session auth + CSRF, no bearer token in the browser).
    Route::post('/stream', [ChatAiController::class, 'stream'])
        ->middleware('throttle:20,1')
        ->name('stream');

    // The old session list is consolidated into the workspace sidebar.
    Route::redirect('/sessions', '/agentic-chat')->name('sessions.index');

    // Read-only history detail, kept for deep links and the eager-loading demo.
    Route::get('/sessions/{id}', [ChatSessionController::class, 'show'])->whereNumber('id')->name('sessions.show');
    Route::get('/sessions/{id}/naive', [ChatSessionController::class, 'showNaive'])->whereNumber('id')->name('sessions.show-naive');
});
