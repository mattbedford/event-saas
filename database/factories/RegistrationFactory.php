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
        return $this->state(function (array $attributes) {
            // Resolve event_id if it's a factory instance
            if ($attributes['event_id'] instanceof \Illuminate\Database\Eloquent\Factories\Factory) {
                $event = $attributes['event_id']->create();
                $attributes['event_id'] = $event->id;
            } else {
                $event = Event::find($attributes['event_id']) ?? Event::factory()->create();
            }

            return [
                'event_id' => $event->id,
                'payment_status' => 'paid',
                'paid_amount' => $event->ticket_price,
                'expected_amount' => $event->ticket_price,
                'stripe_payment_intent_id' => 'pi_' . fake()->uuid(),
                'stripe_session_id' => 'cs_' . fake()->uuid(),
            ];
        });
    }

    /**
     * Partial payment
     */
    public function partial(): static
    {
        return $this->state(function (array $attributes) {
            // Resolve event_id if it's a factory instance
            if ($attributes['event_id'] instanceof \Illuminate\Database\Eloquent\Factories\Factory) {
                $event = $attributes['event_id']->create();
                $attributes['event_id'] = $event->id;
            } else {
                $event = Event::find($attributes['event_id']) ?? Event::factory()->create();
            }

            $partialAmount = $event->ticket_price * 0.5;

            return [
                'event_id' => $event->id,
                'payment_status' => 'partial',
                'paid_amount' => $partialAmount,
                'expected_amount' => $event->ticket_price,
                'discount_amount' => 0,
                'stripe_payment_intent_id' => 'pi_' . fake()->uuid(),
            ];
        });
    }

    /**
     * Failed payment
     */
    public function failed(): static
    {
        return $this->state(function (array $attributes) {
            // Resolve event_id if it's a factory instance
            if ($attributes['event_id'] instanceof \Illuminate\Database\Eloquent\Factories\Factory) {
                $event = $attributes['event_id']->create();
                $attributes['event_id'] = $event->id;
            } else {
                $event = Event::find($attributes['event_id']) ?? Event::factory()->create();
            }

            return [
                'event_id' => $event->id,
                'payment_status' => 'failed',
                'paid_amount' => 0,
                'expected_amount' => $event->ticket_price,
                'stripe_payment_intent_id' => 'pi_' . fake()->uuid(),
            ];
        });
    }

    /**
     * Refunded registration
     */
    public function refunded(): static
    {
        return $this->state(function (array $attributes) {
            // Resolve event_id if it's a factory instance
            if ($attributes['event_id'] instanceof \Illuminate\Database\Eloquent\Factories\Factory) {
                $event = $attributes['event_id']->create();
                $attributes['event_id'] = $event->id;
            } else {
                $event = Event::find($attributes['event_id']) ?? Event::factory()->create();
            }

            return [
                'event_id' => $event->id,
                'payment_status' => 'refunded',
                'paid_amount' => 0,
                'expected_amount' => $event->ticket_price,
            ];
        });
    }

    /**
     * Registration with coupon
     */
    public function withCoupon(string $couponCode = null): static
    {
        return $this->state(function (array $attributes) use ($couponCode) {
            // Resolve event_id if it's a factory instance
            if ($attributes['event_id'] instanceof \Illuminate\Database\Eloquent\Factories\Factory) {
                $event = $attributes['event_id']->create();
                $attributes['event_id'] = $event->id;
            } else {
                $event = Event::find($attributes['event_id']) ?? Event::factory()->create();
            }

            $discountPercent = fake()->randomElement([10, 15, 20, 25, 50]);
            $discountAmount = $event->ticket_price * ($discountPercent / 100);

            return [
                'event_id' => $event->id,
                'coupon_code' => $couponCode ?? 'SAVE' . $discountPercent,
                'discount_amount' => $discountAmount,
                'expected_amount' => $event->ticket_price - $discountAmount,
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
                // Resolve event_id if it's a factory instance
                if ($attributes['event_id'] instanceof \Illuminate\Database\Eloquent\Factories\Factory) {
                    $event = $attributes['event_id']->create();
                } else {
                    $event = Event::find($attributes['event_id']) ?? Event::factory()->create();
                }
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
