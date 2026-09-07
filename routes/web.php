<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;

/*
* Web Routes
*
* Seluruh rute didelegasikan ke PageController sesuai ketentuan tugas.
*
*/

Route::get('/', [PageController::class, 'index'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/project-idea', [PageController::class, 'project'])->name('project');
Route::get('/hitung/{angka1}/{angka2}/{operasi}', [PageController::class, 'hitung'])->name('hitung');
