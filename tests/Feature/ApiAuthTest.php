<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can login with valid credentials and receive token', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'candidate@example.com',
        'password' => 'secret123',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'candidate@example.com',
        'password' => 'secret123',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'data' => [
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'email' => 'candidate@example.com',
                ],
            ],
        ]);

    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
});

test('login fails with invalid credentials', function () {
    $company = Company::factory()->create();
    User::factory()->create([
        'company_id' => $company->id,
        'email' => 'candidate@example.com',
        'password' => 'secret123',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'candidate@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'status' => 'error',
            'message' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
        ]);
});

test('login validation fails when fields are missing', function () {
    $response = $this->postJson('/api/login', []);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => ['email', 'password'],
        ])
        ->assertJson([
            'status' => 'error',
        ]);
});

test('user can register with company name via api', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'password' => 'password123',
        'company_name' => 'PT Teknologi Nusantara',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'message' => 'Registrasi berhasil.',
            'data' => [
                'user' => [
                    'name' => 'Budi Santoso',
                    'email' => 'budi@example.com',
                ],
            ],
        ]);

    $this->assertDatabaseHas('companies', [
        'name' => 'PT Teknologi Nusantara',
    ]);

    $this->assertDatabaseHas('users', [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
    ]);
});

test('user can logout and revoke token', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $token = $user->createToken('test-token')->plainTextToken;

    expect($user->tokens()->count())->toBe(1);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/logout');

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Token berhasil dicabut.',
        ]);

    expect($user->tokens()->count())->toBe(0);
});

test('unauthenticated request to protected route returns 401 json', function () {
    $response = $this->postJson('/api/logout');

    $response->assertStatus(401)
        ->assertJson([
            'status' => 'error',
            'message' => 'Unauthenticated. Token otentikasi tidak ditemukan atau tidak valid.',
        ]);
});
