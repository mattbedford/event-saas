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
            'full_name' => $firstName . ' ' . $lastName,
            'email' => fake()->unique()->safeEmail(),
            'company' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'payment_status' => 'pending',
            'paid_amount' => 0,
            'discount_amount' => 0,
            'metadata' => [],
        ];
    }

    /**
     * Paid registration
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            $event = Event::find($attributes['event_id']) ?? Event::factory()->create();

            return [
                'payment_status' => 'paid',
                'paid_amount' => $event->ticket_price,
                'stripe_payment_intent_id' => 'pi_' . fake()->uuid(),
                'stripe_checkout_session_id' => 'cs_' . fake()->uuid(),
            ];
        });
    }

    /**
     * Partial payment
     */
    public function partial(): static
    {
        return $this->state(function (array $attributes) {
            $event = Event::find($attributes['event_id']) ?? Event::factory()->create();
            $partialAmount = $event->ticket_price * 0.5;

            return [
                'payment_status' => 'partial',
                'paid_amount' => $partialAmount,
                'discount_amount' => $event->ticket_price - $partialAmount,
                'stripe_payment_intent_id' => 'pi_' . fake()->uuid(),
            ];
        });
    }

    /**
     * Failed payment
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'failed',
            'paid_amount' => 0,
            'stripe_payment_intent_id' => 'pi_' . fake()->uuid(),
        ]);
    }

    /**
     * Cancelled registration
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Registration with coupon
     */
    public function withCoupon(string $couponCode = null): static
    {
        return $this->state(function (array $attributes) use ($couponCode) {
            $event = Event::find($attributes['event_id']) ?? Event::factory()->create();
            $discountPercent = fake()->randomElement([10, 15, 20, 25, 50]);
            $discountAmount = $event->ticket_price * ($discountPercent / 100);

            return [
                'coupon_code' => $couponCode ?? 'SAVE' . $discountPercent,
                'discount_amount' => $discountAmount,
            ];
        });
    }

    /**
     * Registration with ticket type
     */
    public function withTicketType(TicketType $ticketType = null): static
    {
        return $this->state(function (array $attributes) use ($ticketType) {
            if (!$ticketType) {
                $event = Event::find($attributes['event_id']) ?? Event::factory()->create();
                $ticketType = TicketType::factory()->for($event)->create();
            }

            return [
                'ticket_type_id' => $ticketType->id,
                'event_id' => $ticketType->event_id,
            ];
        });
    }

    /**
     * Registration for sponsor
     */
    public function sponsor(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
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
            'metadata' => array_merge($attributes['metadata'] ?? [], [
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
            'hubspot_contact_id' => fake()->randomNumber(8, true),
        ]);
    }
}
