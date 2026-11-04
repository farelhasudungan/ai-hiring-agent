<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('users without a company may create one regardless of their current role', function (string $role) {
    $user = User::factory()->withoutCompany()->create(['role' => $role]);

    expect($user->can('create', Company::class))->toBeTrue();
})->with(['admin', 'hr', 'interviewer', 'viewer']);

test('existing company members cannot create another company regardless of role', function (string $role) {
    $user = User::factory()->create(['role' => $role]);

    expect($user->can('create', Company::class))->toBeFalse();
})->with(['admin', 'hr', 'interviewer', 'viewer']);
