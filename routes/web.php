<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\JobPostingController;
use App\Http\Controllers\JobPostingResponseController;
use App\Http\Controllers\RegistrationController;
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
    Route::patch('job-postings/{jobPosting}/status', [JobPostingController::class, 'updateStatus'])->name('job-postings.update-status');
    Route::get('job-postings/{jobPosting}/responses', [JobPostingResponseController::class, 'index'])->name('job-postings.responses.index');
    Route::get('job-postings/{jobPosting}/responses/{formSubmission}', [JobPostingResponseController::class, 'show'])->name('job-postings.responses.show');
    Route::patch('job-postings/{jobPosting}/responses/{formSubmission}/status', [JobPostingResponseController::class, 'updateStatus'])->name('job-postings.responses.update-status');
    Route::delete('job-postings/{jobPosting}/responses/{formSubmission}', [JobPostingResponseController::class, 'destroy'])->name('job-postings.responses.destroy');
    Route::resource('job-postings', JobPostingController::class)->parameters(['job-postings' => 'jobPosting']);

    Route::patch('forms/{form}/status', [FormController::class, 'updateStatus'])->name('forms.update-status');
    Route::put('forms/{form}/schema', [FormController::class, 'updateSchema'])->name('forms.schema.update');
    Route::get('forms/{form}/builder', [FormController::class, 'builder'])->name('forms.builder');
    Route::resource('forms', FormController::class);
});
