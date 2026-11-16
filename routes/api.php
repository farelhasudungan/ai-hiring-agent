<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatAiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// Public authentication routes with rate limiting
Route::middleware('throttle:30,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('api.login');
    Route::post('/register', [AuthController::class, 'register'])->name('api.register');
});

// Authenticated API routes with Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // Secure LLM Chat API Hub with rate limiting
    Route::middleware('throttle:20,1')->group(function () {
        Route::post('/chat-ai', [ChatAiController::class, 'chat'])->name('api.chat-ai');
        Route::post('/chat-ai/stream', [ChatAiController::class, 'stream'])->name('api.chat-ai.stream');
    });
});
