<?php

use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'index'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');

Route::get('/jobs/{job}/apply', [JobApplicationController::class, 'create'])->whereNumber('job')->name('jobs.apply');
Route::post('/jobs/{job}/apply', [JobApplicationController::class, 'store'])
    ->whereNumber('job')->middleware('throttle:5,1')->name('jobs.applications.store');
