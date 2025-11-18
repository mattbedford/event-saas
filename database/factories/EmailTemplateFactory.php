<?php

namespace Database\Factories;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'subject' => fake()->sentence(),
            'html_content' => '<p>Hello {{full_name}},</p><p>' . fake()->paragraph() . '</p>',
            'text_content' => 'Hello {{full_name}}, ' . fake()->paragraph(),
            'is_system' => false,
        ];
    }

    /**
     * System template (cannot be deleted)
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
        ]);
    }
}
