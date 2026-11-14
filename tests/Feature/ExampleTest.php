<?php

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('the landing page renders the hero and links guests to sign up', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Screen candidates in seconds.')
        ->assertSee('Everything essential. Zero clutter.')
        ->assertSee(route('register'))
        ->assertSee(route('login'));
});
