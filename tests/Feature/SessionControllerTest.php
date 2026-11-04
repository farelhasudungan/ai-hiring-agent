<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(LazilyRefreshDatabase::class);

test('the home page keeps the original welcome view for guests', function () {
    $this->get(route('home'))->assertOk()->assertViewIs('welcome');
});

test('the home page keeps the original welcome view for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('home'))->assertOk()->assertViewIs('welcome');
});

test('guests can open the login page', function () {
    $this->get(route('login'))->assertOk()->assertSee('Welcome back.')
        ->assertSee(route('register'))
        ->assertDontSee('<header', false)
        ->assertDontSee('<nav', false)
        ->assertDontSee('<footer', false);
});

test('users can log in with their existing account and rotate their session', function () {
    $user = User::factory()->create();
    $this->withSession(['marker' => 'kept']);
    $sessionId = session()->getId();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirectToRoute('dashboard')->assertSessionHas('marker', 'kept');
    $this->assertAuthenticatedAs($user);
    expect(session()->getId())->not->toBe($sessionId);
});

test('users without a company can log in', function () {
    $user = User::factory()->withoutCompany()->create(['email' => 'recruiter@example.com']);

    $this->post(route('login.store'), [
        'email' => ' RECRUITER@EXAMPLE.COM ',
        'password' => 'password',
    ])->assertRedirectToRoute('dashboard');

    $this->assertAuthenticatedAs($user);
});

test('login returns users to the protected page they originally requested', function () {
    $user = User::factory()->withoutCompany()->create();

    $this->withSession(['url.intended' => route('companies.create')])
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirectToRoute('companies.create');
});

test('remember me sets a persistent authentication cookie', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => '1',
    ])->assertRedirectToRoute('dashboard')->assertCookie(Auth::guard('web')->getRecallerName());

    $this->assertAuthenticatedAs($user);
});

test('invalid credentials do not authenticate a user or flash the password', function () {
    $user = User::factory()->create();

    $this->from(route('login'))->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'incorrect-password',
    ])->assertRedirectToRoute('login')
        ->assertSessionHasErrors(['email' => 'These credentials do not match our records.'])
        ->assertSessionMissingInput('password');

    $this->assertGuest();
});

test('login requires an email address and password', function () {
    $this->post(route('login.store'), [])->assertSessionHasErrors([
        'email' => 'The email field is required.',
        'password' => 'The password field is required.',
    ]);

    $this->assertGuest();
});

test('login validates malformed credentials', function (array $input, string $field) {
    $this->post(route('login.store'), array_replace([
        'email' => 'recruiter@example.com',
        'password' => 'password',
    ], $input))->assertSessionHasErrors($field);

    $this->assertGuest();
})->with([
    'invalid email' => [['email' => 'not-an-email'], 'email'],
    'array email' => [['email' => ['bad']], 'email'],
    'array password' => [['password' => ['bad']], 'password'],
    'long password' => [['password' => str_repeat('a', 73)], 'password'],
    'invalid remember value' => [['remember' => 'yes'], 'remember'],
]);

test('login attempts are rate limited by normalized email and IP', function () {
    $this->freezeTime();

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('login.store'), [
            'email' => 'recruiter@example.com',
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors('email');
    }

    $this->post(route('login.store'), [
        'email' => ' RECRUITER@EXAMPLE.COM ',
        'password' => 'incorrect-password',
    ])->assertTooManyRequests();

    $this->assertGuest();
});

test('authenticated users are redirected away from guest pages', function (string $route) {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route($route))->assertRedirectToRoute('dashboard');
})->with(['login', 'register']);

test('logout invalidates the session and rotates the CSRF token', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['marker' => 'removed', '_token' => 'previous-token']);
    $sessionId = session()->getId();

    $this->post(route('logout'))->assertRedirectToRoute('login')->assertSessionMissing('marker');

    $this->assertGuest();
    expect(session()->getId())->not->toBe($sessionId);
    expect(session()->token())->not->toBe('previous-token');
});

test('logout cannot be triggered by a GET request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/logout')->assertMethodNotAllowed();

    $this->assertAuthenticatedAs($user);
});
