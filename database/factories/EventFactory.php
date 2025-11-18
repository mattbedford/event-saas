<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'ticket_price' => fake()->randomFloat(2, 50, 500),
            'max_seats' => fake()->numberBetween(50, 500),
            'event_date' => fake()->dateTimeBetween('+1 week', '+6 months'),
            'is_active' => true,
            'settings' => [
                'registrations_enabled' => true,
                'badges_enabled' => true,
                'badge_barcode_enabled' => false,
            ],
        ];
    }

    /**
     * Event with no seat limit
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_seats' => null,
        ]);
    }

    /**
     * Event that's nearly full (90%+)
     */
    public function nearlyFull(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_seats' => 100,
        ]);
    }

    /**
     * Event that's sold out
     */
    public function soldOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_seats' => 50,
        ]);
    }

    /**
     * Event with charity enabled
     */
    public function withCharity(): static
    {
        return $this->state(fn (array $attributes) => [
            'charity_enabled' => true,
            'charity_name' => fake()->company() . ' Foundation',
            'charity_description' => fake()->sentence(),
            'charity_donation_url' => fake()->url(),
            'charity_suggested_amount' => fake()->randomFloat(2, 10, 100),
        ]);
    }

    /**
     * Event with automatic invoices disabled
     */
    public function withManualInvoices(): static
    {
        return $this->state(fn (array $attributes) => [
            'automatic_invoices_enabled' => false,
            'invoice_contact_email' => fake()->email(),
        ]);
    }

    /**
     * Event with API key configured
     */
    public function withApiKey(): static
    {
        return $this->state(fn (array $attributes) => [
            'api_key' => 'evt_' . bin2hex(random_bytes(32)),
        ]);
    }

    /**
     * Event with webhook configured
     */
    public function withWebhook(): static
    {
        return $this->state(fn (array $attributes) => [
            'webhook_url' => fake()->url() . '/webhook',
            'webhook_secret' => 'whsec_' . bin2hex(random_bytes(32)),
            'webhook_events' => ['registration.created', 'payment.succeeded'],
        ]);
    }

    /**
     * Inactive event
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Past event
     */
    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_date' => fake()->dateTimeBetween('-6 months', '-1 week'),
        ]);
    }
}
