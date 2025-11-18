<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Registration;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Registration>
 */
class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'event_id' => Event::factory(),
            'name' => $firstName,
            'surname' => $lastName,
            'email' => fake()->unique()->safeEmail(),
            'company' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'payment_status' => 'pending',
            'paid_amount' => 0,
            'expected_amount' => 100, // Default expected amount
            'discount_amount' => 0,
            'additional_fields' => null,
        ];
    }

    /**
     * Paid registration
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
            'paid_amount' => 100, // Will be overridden in tests with actual event price
            'expected_amount' => 100,
            'stripe_payment_intent_id' => 'pi_' . fake()->uuid(),
            'stripe_session_id' => 'cs_' . fake()->uuid(),
        ]);
    }

    /**
     * Partial payment
     */
    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'partial',
            'paid_amount' => 50, // Half payment
            'expected_amount' => 100,
            'discount_amount' => 0,
            'stripe_payment_intent_id' => 'pi_' . fake()->uuid(),
        ]);
    }

    /**
     * Failed payment
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'failed',
            'paid_amount' => 0,
            'expected_amount' => 100,
            'stripe_payment_intent_id' => 'pi_' . fake()->uuid(),
        ]);
    }

    /**
     * Refunded registration
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'refunded',
            'paid_amount' => 0,
            'expected_amount' => 100,
        ]);
    }

    /**
     * Registration with coupon
     */
    public function withCoupon(string $couponCode = null): static
    {
        return $this->state(function (array $attributes) use ($couponCode) {
            $discountPercent = fake()->randomElement([10, 15, 20, 25, 50]);
            $discountAmount = 100 * ($discountPercent / 100);

            return [
                'coupon_code' => $couponCode ?? 'SAVE' . $discountPercent,
                'discount_amount' => $discountAmount,
                'expected_amount' => 100 - $discountAmount,
            ];
        });
    }

    /**
     * Registration with ticket type
     */
    public function withTicketType(?int $ticketTypeId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'ticket_type_id' => $ticketTypeId,
        ]);
    }

    /**
     * Registration for sponsor
     */
    public function sponsor(): static
    {
        return $this->state(fn (array $attributes) => [
            'additional_fields' => array_merge($attributes['additional_fields'] ?? [], [
                'attendee_type' => 'sponsor',
            ]),
        ]);
    }

    /**
     * Registration for brand
     */
    public function brand(): static
    {
        return $this->state(fn (array $attributes) => [
            'additional_fields' => array_merge($attributes['additional_fields'] ?? [], [
                'attendee_type' => 'brand',
            ]),
        ]);
    }

    /**
     * Registration with HubSpot integration
     */
    public function withHubSpot(): static
    {
        return $this->state(fn (array $attributes) => [
            'hubspot_id' => (string) fake()->randomNumber(8, true),
        ]);
    }
}
