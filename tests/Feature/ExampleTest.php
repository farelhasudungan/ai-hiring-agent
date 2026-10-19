<?php

use Illuminate\Support\Facades\Route;

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertViewIs('home')
        ->assertSee('href="'.route('home').'"', false)
        ->assertDontSee('About / Tim Kami');
});

test('the home page shows about links when the about route is available', function () {
    Route::view('/about', 'home')->name('about');
    Route::getRoutes()->refreshNameLookups();

    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('href="'.route('about').'"', false)
        ->assertSee('About / Tim Kami');
});
