<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('guests must log in before opening company setup', function () {
    $this->get(route('companies.create'))->assertRedirectToRoute('login');
});

test('guests cannot create companies', function () {
    $this->post(route('companies.store'), ['name' => 'Acme Studio'])->assertRedirectToRoute('login');

    $this->assertDatabaseCount('companies', 0);
});

test('users without a company can open company setup', function () {
    $user = User::factory()->withoutCompany()->create();

    $this->actingAs($user)->get(route('companies.create'))->assertOk()->assertSee('Company name');
});

test('users can create a free company and become its admin', function () {
    $user = User::factory()->withoutCompany()->create();

    $this->actingAs($user)->post(route('companies.store'), [
        'name' => 'Acme Studio',
        'plan' => 'enterprise',
        'role' => 'viewer',
        'slug' => 'untrusted-slug',
        'company_id' => 999,
    ])->assertRedirectToRoute('dashboard')->assertSessionHas('status', 'Your company is ready. Welcome aboard!');

    $company = Company::query()->sole();
    $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'Acme Studio', 'plan' => 'free']);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'company_id' => $company->id, 'role' => 'admin']);
    expect($company->slug)->toStartWith('acme-studio-')->not->toBe('untrusted-slug');
});

test('companies with the same name receive different slugs', function () {
    $existing = Company::factory()->create(['name' => 'Acme Studio', 'slug' => 'acme-studio']);
    $user = User::factory()->withoutCompany()->create();

    $this->actingAs($user)->post(route('companies.store'), ['name' => 'Acme Studio'])->assertRedirectToRoute('dashboard');

    $this->assertDatabaseCount('companies', 2);
    expect($user->fresh()->company->slug)->not->toBe($existing->slug);
});

test('company creation validates its name without making changes', function (mixed $name, string $message) {
    $user = User::factory()->withoutCompany()->create();

    $this->actingAs($user)->post(route('companies.store'), ['name' => $name])
        ->assertSessionHasErrors(['name' => $message]);

    $this->assertDatabaseCount('companies', 0);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'company_id' => null, 'role' => 'viewer']);
})->with([
    'missing name' => [null, 'The name field is required.'],
    'blank name' => ['   ', 'The name field is required.'],
    'array name' => [['bad'], 'The name field must be a string.'],
    'long name' => [str_repeat('a', 256), 'The name field must not be greater than 255 characters.'],
]);

test('company members cannot open company setup', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('companies.create'))->assertForbidden();
});

test('company members cannot create a second company or replace their membership', function () {
    $user = User::factory()->create();
    $companyId = $user->company_id;

    $this->actingAs($user)->post(route('companies.store'), ['name' => 'Another company'])->assertForbidden();

    $this->assertDatabaseCount('companies', 1);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'company_id' => $companyId, 'role' => 'viewer']);
});
