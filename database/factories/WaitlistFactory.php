<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Waitlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Waitlist>
 */
class WaitlistFactory extends Factory
{
    protected $model = Waitlist::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'status' => 'waiting',
        ];
    }

    /**
     * Notified status
     */
    public function notified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'notified',
            'notified_at' => now()->subHours(12),
            'expires_at' => now()->addHours(12),
        ]);
    }

    /**
     * Notification expired
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'notified_at' => now()->subDays(2),
            'expires_at' => now()->subHours(25),
        ]);
    }

    /**
     * Converted to registration
     */
    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'converted',
            'notified_at' => now()->subHours(12),
            'expires_at' => now()->addHours(12),
        ]);
    }
}
