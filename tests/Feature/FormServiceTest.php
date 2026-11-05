<?php

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use App\Services\FormService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('it generates unique answer keys for fields with the same label', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create(['company_id' => $user->company_id]);

    app(FormService::class)->syncSchema($user, $form, [
        [
            'title' => 'Questions',
            'fields' => [
                ['label' => 'Role', 'type' => 'short_text', 'options' => []],
                ['label' => 'Role', 'type' => 'short_text', 'options' => []],
            ],
        ],
    ]);

    expect($form->fresh()->fields->pluck('field_key')->all())->toBe(['role', 'role_2']);
});

test('it rejects duplicate explicit answer keys', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create(['company_id' => $user->company_id]);

    app(FormService::class)->syncSchema($user, $form, [
        [
            'title' => 'Questions',
            'fields' => [
                ['label' => 'First', 'type' => 'short_text', 'field_key' => 'same_key', 'options' => []],
                ['label' => 'Second', 'type' => 'short_text', 'field_key' => 'same_key', 'options' => []],
            ],
        ],
    ]);
})->throws(DomainException::class, 'Two questions have the same answer key. Give each question a different answer key.');

test('it prevents a user from changing a form from another company', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create();

    app(FormService::class)->updateStatus($user, $form, 'closed');
})->throws(DomainException::class, 'This form does not belong to your company.');

test('it reports how many responses were deleted with a form', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create(['company_id' => $user->company_id]);
    FormSubmission::factory()->count(2)->create(['form_id' => $form->id]);

    $deleted = app(FormService::class)->delete($user, $form);

    expect($deleted)->toBe(2)
        ->and(Form::query()->whereKey($form->id)->exists())->toBeFalse();
});
