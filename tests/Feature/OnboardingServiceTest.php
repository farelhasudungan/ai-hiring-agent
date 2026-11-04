<?php

use App\Models\Company;
use App\Models\User;
use App\Services\OnboardingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('company creation rechecks membership even when the supplied user is stale', function () {
    $user = User::factory()->withoutCompany()->create();
    $company = Company::factory()->create();
    User::query()->whereKey($user->id)->update(['company_id' => $company->id]);

    expect(fn () => app(OnboardingService::class)->createCompany($user, 'Another company'))
        ->toThrow(DomainException::class, 'You already belong to a company.');

    $this->assertDatabaseCount('companies', 1);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'company_id' => $company->id, 'role' => 'viewer']);
});

test('company creation rolls back when membership cannot be saved', function () {
    $user = User::factory()->withoutCompany()->create();
    User::updating(function (User $user): void {
        throw new RuntimeException('Membership could not be saved.');
    });

    expect(fn () => app(OnboardingService::class)->createCompany($user, 'Acme Studio'))
        ->toThrow(RuntimeException::class, 'Membership could not be saved.');

    $this->assertDatabaseCount('companies', 0);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'company_id' => null, 'role' => 'viewer']);
});

test('company slugs handle names without Latin characters and maximum length names', function (string $name) {
    $user = User::factory()->withoutCompany()->create();

    $company = app(OnboardingService::class)->createCompany($user, $name);

    $this->assertModelExists($company);
    expect($company->slug)->not->toBeEmpty();
    expect(mb_strlen($company->slug))->toBeLessThanOrEqual(255);
})->with([
    'non Latin name' => ['株式会社'],
    'maximum length' => [str_repeat('a', 255)],
]);
