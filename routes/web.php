<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
* Web Routes
*
* Seluruh rute didelegasikan ke PageController sesuai ketentuan tugas.
*
*/

Route::get('/', [PageController::class, 'index'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::redirect('/project-idea', '/', 301);
