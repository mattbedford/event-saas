<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'code' => strtoupper(fake()->bothify('????##')),
            'discount_type' => fake()->randomElement(['percentage', 'fixed']),
            'discount_value' => fake()->randomFloat(2, 10, 50),
            'max_uses' => fake()->numberBetween(5, 100),
            'used_count' => 0,
            'is_active' => true,
        ];
    }

    /**
     * Percentage discount coupon
     */
    public function percentage(float $value = 20): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => 'percentage',
            'discount_value' => $value,
            'code' => 'SAVE' . intval($value),
        ]);
    }

    /**
     * Fixed amount discount coupon
     */
    public function fixed(float $value = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => 'fixed',
            'discount_value' => $value,
            'code' => 'OFF' . intval($value),
        ]);
    }

    /**
     * Single use coupon
     */
    public function singleUse(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_uses' => 1,
        ]);
    }

    /**
     * Nearly exhausted coupon (1 use remaining)
     */
    public function nearlyExhausted(): static
    {
        return $this->state(function (array $attributes) {
            $limit = $attributes['max_uses'] ?? 10;
            return [
                'max_uses' => $limit,
                'used_count' => $limit - 1,
            ];
        });
    }

    /**
     * Fully used coupon
     */
    public function exhausted(): static
    {
        return $this->state(function (array $attributes) {
            $limit = $attributes['max_uses'] ?? 10;
            return [
                'max_uses' => $limit,
                'used_count' => $limit,
            ];
        });
    }


    /**
     * Inactive coupon
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Coupon with validity period
     */
    public function withValidityPeriod(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_from' => now()->subDays(7),
            'valid_until' => now()->addDays(7),
        ]);
    }

    /**
     * Expired coupon (past valid_until date)
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_from' => now()->subDays(30),
            'valid_until' => now()->subDays(1),
        ]);
    }

    /**
     * Not yet valid coupon (future valid_from date)
     */
    public function notYetValid(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_from' => now()->addDays(7),
            'valid_until' => now()->addDays(14),
        ]);
    }
}
