<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('a success flash message is rendered as a success toast', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->withSession(['status' => 'Job posting created.'])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('id="toast-region"', false)
        ->assertSee('Job posting created.')
        ->assertSee('&quot;type&quot;:&quot;success&quot;', false);
});

test('an error flash message is rendered as an error toast', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->withSession(['error' => 'Something went wrong.'])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Something went wrong.')
        ->assertSee('&quot;type&quot;:&quot;error&quot;', false);
});

test('validation errors produce an error toast', function () {
    $this->from(route('login'))->post(route('login.store'), ['email' => 'nobody@example.com', 'password' => 'wrong'])
        ->assertRedirect(route('login'));

    $this->get(route('login'))->assertSee('Please check the highlighted fields');
});

test('no toasts are rendered without flash messages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('data-toasts="[]"', false);
});

test('messages containing quotes do not break the toast markup', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->withSession(['status' => "It's \"done\""])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('It&#039;s', false);
});
