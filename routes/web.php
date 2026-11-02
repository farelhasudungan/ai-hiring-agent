<?php

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
    Route::get('/sessions', [ChatSessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/{id}', [ChatSessionController::class, 'show'])->whereNumber('id')->name('sessions.show');
    Route::get('/sessions/{id}/naive', [ChatSessionController::class, 'showNaive'])->whereNumber('id')->name('sessions.show-naive');
});
