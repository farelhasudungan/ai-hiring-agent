<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\FormField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormField>
 */
class FormFieldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'form_section_id' => null,
            'field_key' => fake()->unique()->slug(2),
            'label' => fake()->sentence(3),
            'type' => 'short_text',
            'help_text' => null,
            'placeholder' => null,
            'is_required' => true,
            'validation' => null,
            'conditional_logic' => null,
            'default_value' => null,
            'maps_to' => null,
            'sort_order' => 0,
        ];
    }
}
