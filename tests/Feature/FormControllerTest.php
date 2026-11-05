<?php

use App\Models\Form;
use App\Models\FormAnswer;
use App\Models\FormField;
use App\Models\FormSection;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('guests must log in before opening forms', function () {
    $this->get(route('forms.index'))->assertRedirectToRoute('login');
    $this->post(route('forms.store'), [])->assertRedirectToRoute('login');
});

test('users without a company cannot open forms', function () {
    $user = User::factory()->withoutCompany()->create();

    $this->actingAs($user)->get(route('forms.index'))->assertForbidden();
    $this->actingAs($user)->get(route('forms.create'))->assertForbidden();
});

test('the index only lists forms from the current company', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $ownForm = Form::factory()->create(['company_id' => $user->company_id, 'title' => 'Own company form']);
    Form::factory()->create(['title' => 'Other company form']);

    $this->actingAs($user)->get(route('forms.index'))
        ->assertOk()
        ->assertSee($ownForm->title)
        ->assertDontSee('Other company form');
});

test('viewers can browse forms but cannot manage them', function () {
    $user = User::factory()->create(['role' => 'viewer']);
    $form = Form::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)->get(route('forms.index'))->assertOk();
    $this->actingAs($user)->get(route('forms.show', $form))->assertOk();
    $this->actingAs($user)->get(route('forms.create'))->assertForbidden();
    $this->actingAs($user)->get(route('forms.builder', $form))->assertForbidden();

    $this->actingAs($user)->post(route('forms.store'), [
        'title' => 'Should not exist',
        'status' => 'draft',
    ])->assertForbidden();

    $this->actingAs($user)->put(route('forms.update', $form), [
        'title' => 'Changed',
        'status' => 'draft',
    ])->assertForbidden();

    $this->actingAs($user)->delete(route('forms.destroy', $form))->assertForbidden();

    $this->assertDatabaseHas('forms', ['id' => $form->id, 'title' => $form->title]);
});

test('admins can create a form with a confirmation message', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)->post(route('forms.store'), [
        'title' => 'Engineering application',
        'description' => 'Tell us about yourself.',
        'status' => 'published',
        'closes_at' => now()->addMonth()->format('Y-m-d H:i:s'),
        'confirmation_message' => 'Thanks for applying!',
        'company_id' => 999,
    ])->assertRedirect();

    $form = Form::query()->sole();

    expect($form->company_id)->toBe($user->company_id)
        ->and($form->created_by)->toBe($user->id)
        ->and($form->status)->toBe('published')
        ->and($form->published_at)->not->toBeNull()
        ->and($form->settings['confirmation_message'])->toBe('Thanks for applying!');
});

test('creating a form validates its input', function (array $payload, string $field) {
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)->post(route('forms.store'), $payload)
        ->assertSessionHasErrors($field);

    $this->assertDatabaseCount('forms', 0);
})->with([
    'missing title' => [['status' => 'draft'], 'title'],
    'invalid status' => [['title' => 'Form', 'status' => 'archived'], 'status'],
    'invalid close date' => [['title' => 'Form', 'status' => 'draft', 'closes_at' => 'not-a-date'], 'closes_at'],
]);

test('admins can update form details and clear the confirmation message', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create([
        'company_id' => $user->company_id,
        'status' => 'draft',
        'published_at' => null,
        'settings' => ['confirmation_message' => 'Old message'],
    ]);

    $this->actingAs($user)->put(route('forms.update', $form), [
        'title' => 'Updated form',
        'description' => 'Updated description.',
        'status' => 'published',
        'confirmation_message' => '',
    ])->assertRedirectToRoute('forms.show', $form);

    $form->refresh();

    expect($form->title)->toBe('Updated form')
        ->and($form->status)->toBe('published')
        ->and($form->published_at)->not->toBeNull()
        ->and($form->settings)->toBeNull();
});

test('admins can update a form status', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create(['company_id' => $user->company_id, 'status' => 'draft', 'published_at' => null]);

    $this->actingAs($user)->patch(route('forms.update-status', $form), ['status' => 'closed'])
        ->assertRedirect();

    $form->refresh();

    expect($form->status)->toBe('closed')
        ->and($form->published_at)->toBeNull();
});

test('the builder lists sections and fields from the form', function () {
    $user = User::factory()->create(['role' => 'hr']);
    $form = Form::factory()->create(['company_id' => $user->company_id]);
    $section = FormSection::factory()->create(['form_id' => $form->id, 'title' => 'Personal details']);
    FormField::factory()->create(['form_id' => $form->id, 'form_section_id' => $section->id, 'label' => 'Full name']);

    $this->actingAs($user)->get(route('forms.builder', $form))
        ->assertOk()
        ->assertSee('Personal details')
        ->assertSee('Full name')
        ->assertSee('Add section')
        ->assertSee('data-form-builder', false)
        ->assertSee('id="section-template"', false)
        ->assertSee('id="field-template"', false);
});

test('admins can save a form structure with sections fields and options', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)->put(route('forms.schema.update', $form), [
        'sections' => [
            [
                'title' => 'Personal information',
                'description' => 'A little about you.',
                'fields' => [
                    [
                        'label' => 'Full name',
                        'type' => 'short_text',
                        'field_key' => 'full_name',
                        'is_required' => '1',
                        'maps_to' => 'candidate.full_name',
                        'options' => [],
                    ],
                    [
                        'label' => 'Primary skills',
                        'type' => 'checkboxes',
                        'options' => [
                            ['label' => 'PHP'],
                            ['label' => 'Laravel', 'value' => 'laravel'],
                        ],
                    ],
                ],
            ],
        ],
    ])->assertRedirectToRoute('forms.show', $form);

    $form->refresh();

    expect($form->version)->toBe(2)
        ->and($form->sections)->toHaveCount(1)
        ->and($form->fields)->toHaveCount(2)
        ->and($form->fields->firstWhere('field_key', 'full_name')->maps_to)->toBe('candidate.full_name')
        ->and($form->fields->firstWhere('label', 'Primary skills')->options->pluck('value')->all())
        ->toBe(['php', 'laravel']);
});

test('a structure can be saved when the browser omits empty fields and options arrays', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)->put(route('forms.schema.update', $form), [
        'sections' => [
            [
                'title' => 'Empty section',
            ],
            [
                'title' => 'Questions',
                'fields' => [
                    [
                        'label' => 'Full name',
                        'type' => 'short_text',
                    ],
                ],
            ],
        ],
    ])->assertRedirectToRoute('forms.show', $form);

    expect($form->fresh()->sections)->toHaveCount(2)
        ->and($form->fresh()->fields)->toHaveCount(1)
        ->and($form->fresh()->fields->first()->field_key)->toBe('full_name');
});

test('invalid builder input shows friendly inline errors and keeps unsaved input', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)
        ->from(route('forms.builder', $form))
        ->followingRedirects()
        ->put(route('forms.schema.update', $form), [
            'sections' => [
                [
                    'title' => 'Questions',
                    'fields' => [
                        [
                            'label' => 'Primary skill',
                            'type' => 'dropdown',
                            'field_key' => 'primary_skill',
                            'options' => [
                                ['label' => ''],
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->assertOk()
        ->assertSee('Some fields need your attention.')
        ->assertSee('Give this option a label, or remove the empty option.')
        ->assertSee('Primary skill')
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('border-red-700', false)
        ->assertDontSee('Please fix the following')
        ->assertDontSee('The sections.0.fields.0.options.0.label field is required.');
});

test('hidden option rows on non-choice fields do not block saving', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)->put(route('forms.schema.update', $form), [
        'sections' => [
            [
                'title' => 'Questions',
                'fields' => [
                    [
                        'label' => 'Full name',
                        'type' => 'short_text',
                        'options' => [
                            ['label' => ''],
                        ],
                    ],
                ],
            ],
        ],
    ])->assertRedirectToRoute('forms.show', $form);

    expect($form->fresh()->fields->first()->options)->toHaveCount(0);
});

test('builder errors stay attached to the right option when indexes have gaps', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)
        ->from(route('forms.builder', $form))
        ->followingRedirects()
        ->put(route('forms.schema.update', $form), [
            'sections' => [
                2 => [
                    'title' => 'Questions',
                    'fields' => [
                        3 => [
                            'label' => 'Primary skill',
                            'type' => 'dropdown',
                            'options' => [
                                4 => ['label' => ''],
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->assertOk()
        ->assertSee('sections[2][fields][3][options][4][label]', false)
        ->assertSee('Give this option a label, or remove the empty option.')
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('border-red-700', false);
});

test('form structure edits are allowed after responses exist', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create(['company_id' => $user->company_id]);
    $section = FormSection::factory()->create(['form_id' => $form->id, 'title' => 'Old section']);
    $field = FormField::factory()->create([
        'form_id' => $form->id,
        'form_section_id' => $section->id,
        'field_key' => 'full_name',
        'label' => 'Old label',
    ]);
    $submission = FormSubmission::factory()->create(['form_id' => $form->id]);
    FormAnswer::factory()->create(['form_submission_id' => $submission->id, 'form_field_id' => $field->id, 'value' => 'Jane']);

    $this->actingAs($user)->put(route('forms.schema.update', $form), [
        'sections' => [
            [
                'id' => $section->id,
                'title' => 'Updated section',
                'fields' => [
                    [
                        'id' => $field->id,
                        'label' => 'Updated label',
                        'type' => 'short_text',
                        'field_key' => 'full_name',
                        'options' => [],
                    ],
                ],
            ],
        ],
    ])->assertRedirectToRoute('forms.show', $form);

    $field->refresh();

    expect($field->label)->toBe('Updated label')
        ->and($field->answers()->count())->toBe(1);
});

test('form structure edits cannot remove a field that already has answers', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create(['company_id' => $user->company_id]);
    $section = FormSection::factory()->create(['form_id' => $form->id]);
    $field = FormField::factory()->create(['form_id' => $form->id, 'form_section_id' => $section->id]);
    $submission = FormSubmission::factory()->create(['form_id' => $form->id]);
    FormAnswer::factory()->create(['form_submission_id' => $submission->id, 'form_field_id' => $field->id]);

    $this->actingAs($user)->put(route('forms.schema.update', $form), [
        'sections' => [
            [
                'id' => $section->id,
                'title' => 'Section',
                'fields' => [],
            ],
        ],
    ])->assertSessionHasErrors('schema');

    $this->assertDatabaseHas('form_fields', ['id' => $field->id]);
});

test('a schema edit cannot reference sections from another form', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create(['company_id' => $user->company_id]);
    $foreignSection = FormSection::factory()->create();

    $this->actingAs($user)->put(route('forms.schema.update', $form), [
        'sections' => [
            [
                'id' => $foreignSection->id,
                'title' => 'Hijacked section',
            ],
        ],
    ])->assertSessionHasErrors('sections.0.id');

    $foreignSection->refresh();

    expect($foreignSection->title)->not->toBe('Hijacked section')
        ->and($foreignSection->form_id)->not->toBe($form->id);
});

test('deleting a form also deletes its responses', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create(['company_id' => $user->company_id]);
    $field = FormField::factory()->create(['form_id' => $form->id]);
    $submission = FormSubmission::factory()->create(['form_id' => $form->id]);
    $answer = FormAnswer::factory()->create([
        'form_submission_id' => $submission->id,
        'form_field_id' => $field->id,
    ]);

    $this->actingAs($user)->delete(route('forms.destroy', $form))
        ->assertRedirectToRoute('forms.index');

    $this->assertDatabaseMissing('forms', ['id' => $form->id]);
    $this->assertDatabaseMissing('form_submissions', ['id' => $submission->id]);
    $this->assertDatabaseMissing('form_answers', ['id' => $answer->id]);
});

test('users cannot manage forms that belong to another company', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $form = Form::factory()->create();

    $this->actingAs($user)->get(route('forms.show', $form))->assertForbidden();
    $this->actingAs($user)->get(route('forms.builder', $form))->assertForbidden();
    $this->actingAs($user)->put(route('forms.update', $form), [
        'title' => 'Hijacked',
        'status' => 'draft',
    ])->assertForbidden();
    $this->actingAs($user)->delete(route('forms.destroy', $form))->assertForbidden();
});
