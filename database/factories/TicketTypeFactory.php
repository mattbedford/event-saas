<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TicketType>
 */
class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->randomElement(['Early Bird', 'Standard', 'VIP', 'Group Rate']),
            'price' => fake()->randomFloat(2, 50, 300),
            'quantity_available' => fake()->numberBetween(20, 100),
            'quantity_sold' => 0,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Early bird ticket
     */
    public function earlyBird(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Early Bird',
            'sale_starts_at' => now()->subDays(30),
            'sale_ends_at' => now()->addDays(7),
        ]);
    }

    /**
     * Currently on sale
     */
    public function onSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'sale_starts_at' => now()->subDays(7),
            'sale_ends_at' => now()->addDays(30),
        ]);
    }

    /**
     * Sale ended
     */
    public function saleEnded(): static
    {
        return $this->state(fn (array $attributes) => [
            'sale_starts_at' => now()->subDays(30),
            'sale_ends_at' => now()->subDays(1),
        ]);
    }

    /**
     * Sale not yet started
     */
    public function saleNotStarted(): static
    {
        return $this->state(fn (array $attributes) => [
            'sale_starts_at' => now()->addDays(7),
            'sale_ends_at' => now()->addDays(30),
        ]);
    }

    /**
     * Nearly sold out
     */
    public function nearlySoldOut(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity_available'] ?? 100;
            return [
                'quantity_available' => $quantity,
                'quantity_sold' => $quantity - 1,
            ];
        });
    }

    /**
     * Sold out
     */
    public function soldOut(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity_available'] ?? 100;
            return [
                'quantity_available' => $quantity,
                'quantity_sold' => $quantity,
            ];
        });
    }

    /**
     * Unlimited quantity
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity_available' => null,
        ]);
    }

    /**
     * Inactive ticket type
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
