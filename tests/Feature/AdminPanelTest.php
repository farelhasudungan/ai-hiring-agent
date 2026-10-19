<?php

test('guests can view the admin login page', function () {
    $response = $this->get(route('filament.admin.auth.login'));

    $response->assertOk()
        ->assertSee('Email address')
        ->assertSee('Password');
});

test('guests are redirected to login when accessing the admin dashboard', function () {
    $response = $this->get(route('filament.admin.pages.dashboard'));

    $response->assertRedirectToRoute('filament.admin.auth.login');
});
