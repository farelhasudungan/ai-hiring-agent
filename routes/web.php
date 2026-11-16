<?php

use App\Http\Controllers\AiSettingController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobPostingController;
use App\Http\Controllers\PdfIntakeController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ScreeningController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

Route::get('/', function (): View {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::get('/signup', [RegistrationController::class, 'create'])->name('register');
    Route::post('/signup', [RegistrationController::class, 'store'])->middleware('throttle:registration')->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
    Route::resource('companies', CompanyController::class)->only(['create', 'store']);
    Route::resource('job-postings', JobPostingController::class);
    Route::get('/settings/ai', [AiSettingController::class, 'edit'])->name('settings.ai.edit');
    Route::post('/settings/ai', [AiSettingController::class, 'update'])->name('settings.ai.update');
    Route::post('/settings/ai/models', [AiSettingController::class, 'models'])->middleware('throttle:ai-connection-test')->name('settings.ai.models');
    Route::post('/settings/ai/test', [AiSettingController::class, 'test'])->middleware('throttle:ai-connection-test')->name('settings.ai.test');
    Route::post('job-postings/{jobPosting}/intake/pdf', [PdfIntakeController::class, 'store'])->name('job-postings.intake.pdf');
    Route::get('job-postings/{jobPosting}/candidates/{application}', [CandidateController::class, 'show'])->scopeBindings()->name('job-postings.candidates.show');
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
    Route::get('/chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{conversation}/messages', [ChatController::class, 'storeMessage'])->middleware('throttle:ai-chat')->name('chat.messages.store');
    Route::post('job-postings/{jobPosting}/screening', [ScreeningController::class, 'store'])->name('job-postings.screening.store');
});
