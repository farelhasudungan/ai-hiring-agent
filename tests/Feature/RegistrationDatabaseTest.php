<?php

use App\Filament\Auth\Register;
use App\Filament\Pages\CompanyInvitations;
use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\User;
use Filament\Auth\Events\Registered;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('accounts and companies are created together only after the third step', function () {
    Event::fake([Registered::class]);
    $page = Livewire::test(Register::class)
        ->set('data.email', 'founder@example.com')
        ->call('nextStep')
        ->set('data.name', 'Nadia')
        ->set('data.password', 'secure-password')
        ->call('nextStep');

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('companies', 0);

    $page->set('data.company_name', str_repeat('N', 255))
        ->set('data.role', 'viewer')
        ->set('data.company_id', 999)
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(Filament::getUrl());

    $user = User::query()->sole();
    expect($user->role)->toBe('admin');
    expect($user->company->plan)->toBe('free');
    expect(strlen($user->company->slug))->toBeLessThanOrEqual(255);
    expect(Hash::check('secure-password', $user->password))->toBeTrue();
    $this->assertAuthenticatedAs($user);
    Event::assertDispatched(Registered::class, fn ($event) => $event->getUser()->is($user));
});

test('registered emails cannot advance', function () {
    $user = User::factory()->create();

    Livewire::test(Register::class)
        ->set('data.email', $user->email)
        ->call('nextStep')
        ->assertHasErrors(['data.email' => 'unique'])
        ->assertSet('step', 1);

    $this->assertDatabaseCount('users', 1);
});

test('joining uses the invitation company and role and consumes it once', function () {
    $token = str_repeat('a', 64);
    $invitation = CompanyInvitation::factory()->create([
        'email' => 'invitee@example.com',
        'role' => 'hr',
        'token_hash' => hash('sha256', $token),
    ]);

    Livewire::test(Register::class)
        ->set('data.email', 'invitee@example.com')
        ->call('nextStep')
        ->set('data.name', 'Nadia')
        ->set('data.password', 'secure-password')
        ->call('nextStep')
        ->set('data.company_mode', 'join')
        ->set('data.invitation', $token)
        ->set('data.role', 'admin')
        ->set('data.company_id', 999)
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(Filament::getUrl());

    $user = User::query()->sole();
    expect($user->company_id)->toBe($invitation->company_id);
    expect($user->role)->toBe('hr');
    expect($invitation->fresh()->accepted_at)->not->toBeNull();
    $this->assertDatabaseCount('companies', 1);
});

test('invalid invitations cannot create an account', function (string $reason) {
    $token = str_repeat('a', 64);
    $invitation = CompanyInvitation::factory()->create([
        'email' => $reason === 'wrong recipient' ? 'other@example.com' : 'invitee@example.com',
        'token_hash' => hash('sha256', $reason === 'unknown token' ? 'different-token' : $token),
        'expires_at' => $reason === 'expired' ? now()->subMinute() : now()->addDay(),
        'accepted_at' => $reason === 'used' ? now() : null,
    ]);

    Livewire::test(Register::class)
        ->set('data.email', 'invitee@example.com')
        ->call('nextStep')
        ->set('data.name', 'Nadia')
        ->set('data.password', 'secure-password')
        ->call('nextStep')
        ->set('data.company_mode', 'join')
        ->set('data.invitation', $token)
        ->call('register')
        ->assertHasErrors('data.invitation')
        ->assertNoRedirect();

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('companies', 1);
    expect($invitation->fresh()->accepted_at?->toDateTimeString())->toBe($invitation->accepted_at?->toDateTimeString());
    $this->assertGuest();
})->with(['unknown token', 'wrong recipient', 'expired', 'used']);

test('company creation is rolled back if account persistence fails', function () {
    Event::listen('eloquent.creating: '.User::class, function (): never {
        throw new RuntimeException('Account persistence failed.');
    });

    $page = Livewire::test(Register::class)
        ->set('data.email', 'founder@example.com')
        ->call('nextStep')
        ->set('data.name', 'Nadia')
        ->set('data.password', 'secure-password')
        ->call('nextStep')
        ->set('data.company_name', 'Nusantara');

    expect(fn () => $page->call('register'))->toThrow(RuntimeException::class, 'Account persistence failed.');
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('companies', 0);
    $this->assertGuest();
});

test('admins issue hashed invitations only for their own company', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $otherCompany = Company::factory()->create();

    $page = Livewire::actingAs($user)->test(CompanyInvitations::class)
        ->set('data.email', 'invitee@example.com')
        ->set('data.role', 'viewer')
        ->set('data.company_id', $otherCompany->id)
        ->call('createInvitation')
        ->assertHasNoErrors();

    $invitation = CompanyInvitation::query()->sole();
    parse_str(parse_url($page->get('invitationUrl'), PHP_URL_QUERY), $query);
    expect($invitation->company_id)->toBe($user->company_id);
    expect($invitation->created_by)->toBe($user->id);
    expect($invitation->token_hash)->toBe(hash('sha256', $query['invitation']));
    expect($invitation->token_hash)->not->toBe($query['invitation']);
});

test('non admins cannot issue invitations', function () {
    $user = User::factory()->create(['role' => 'hr']);

    Livewire::actingAs($user)->test(CompanyInvitations::class)->assertForbidden();

    $this->assertDatabaseCount('company_invitations', 0);
});

test('invitation roles cannot be forged', function () {
    $user = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($user)->test(CompanyInvitations::class)
        ->set('data.email', 'invitee@example.com')
        ->set('data.role', 'admin')
        ->call('createInvitation')
        ->assertHasErrors('data.role');

    $this->assertDatabaseCount('company_invitations', 0);
});
