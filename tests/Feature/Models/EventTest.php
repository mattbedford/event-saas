<?php

namespace Tests\Feature\Models;

use App\Models\Coupon;
use App\Models\Event;
use App\Models\Registration;
use App\Models\TicketType;
use App\Models\Waitlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_registrations_relationship()
    {
        $event = Event::factory()->create();
        Registration::factory()->for($event)->count(3)->create();

        $this->assertCount(3, $event->registrations);
    }

    /** @test */
    public function it_has_coupons_relationship()
    {
        $event = Event::factory()->create();
        Coupon::factory()->for($event)->count(2)->create();

        $this->assertCount(2, $event->coupons);
    }

    /** @test */
    public function it_has_ticket_types_relationship()
    {
        $event = Event::factory()->create();
        TicketType::factory()->for($event)->count(3)->create();

        $this->assertCount(3, $event->ticketTypes);
    }

    /** @test */
    public function it_has_waitlists_relationship()
    {
        $event = Event::factory()->create();
        Waitlist::factory()->for($event)->count(5)->create();

        $this->assertCount(5, $event->waitlists);
    }

    /** @test */
    public function it_counts_paid_registrations()
    {
        $event = Event::factory()->create();
        Registration::factory()->for($event)->paid()->count(5)->create();
        Registration::factory()->for($event)->count(3)->create(['payment_status' => 'pending']);

        $this->assertEquals(5, $event->paidRegistrationsCount());
    }

    /** @test */
    public function it_counts_active_registrations_including_pending()
    {
        $event = Event::factory()->create();
        Registration::factory()->for($event)->paid()->count(5)->create();
        Registration::factory()->for($event)->count(3)->create(['payment_status' => 'pending']);
        Registration::factory()->for($event)->count(2)->create(['payment_status' => 'failed']);

        // Should count paid + pending = 8
        $this->assertEquals(8, $event->activeRegistrationsCount());
    }

    /** @test */
    public function it_calculates_total_revenue()
    {
        $event = Event::factory()->create();
        Registration::factory()->for($event)->paid()->count(3)->create(['paid_amount' => 100]);
        Registration::factory()->for($event)->partial()->count(2)->create(['paid_amount' => 50]);
        Registration::factory()->for($event)->count(2)->create(['payment_status' => 'pending', 'paid_amount' => 0]);

        // 3 * 100 + 2 * 50 = 400
        $this->assertEquals(400, $event->totalRevenue());
    }

    /** @test */
    public function it_calculates_remaining_seats()
    {
        $event = Event::factory()->create(['max_seats' => 100]);
        Registration::factory()->for($event)->paid()->count(30)->create();
        Registration::factory()->for($event)->count(10)->create(['payment_status' => 'pending']);

        $this->assertEquals(60, $event->remainingSeats());
    }

    /** @test */
    public function it_returns_null_for_unlimited_seats()
    {
        $event = Event::factory()->unlimited()->create();

        $this->assertNull($event->remainingSeats());
    }

    /** @test */
    public function it_returns_zero_when_sold_out()
    {
        $event = Event::factory()->create(['max_seats' => 50]);
        Registration::factory()->for($event)->paid()->count(50)->create();

        $this->assertEquals(0, $event->remainingSeats());
    }

    /** @test */
    public function it_checks_if_seats_are_available()
    {
        $event = Event::factory()->create(['max_seats' => 100]);
        Registration::factory()->for($event)->paid()->count(50)->create();

        $this->assertTrue($event->hasAvailableSeats());
    }

    /** @test */
    public function it_detects_sold_out_event()
    {
        $event = Event::factory()->soldOut()->create();
        Registration::factory()->for($event)->paid()->count(50)->create();

        $this->assertFalse($event->hasAvailableSeats());
    }

    /** @test */
    public function it_detects_unlimited_capacity_event_has_seats()
    {
        $event = Event::factory()->unlimited()->create();
        Registration::factory()->for($event)->paid()->count(1000)->create();

        $this->assertTrue($event->hasAvailableSeats());
    }

    /** @test */
    public function it_detects_nearly_full_event()
    {
        $event = Event::factory()->nearlyFull()->create();
        Registration::factory()->for($event)->paid()->count(91)->create();

        $this->assertTrue($event->isNearlyFull());
    }

    /** @test */
    public function it_detects_event_not_nearly_full()
    {
        $event = Event::factory()->create(['max_seats' => 100]);
        Registration::factory()->for($event)->paid()->count(50)->create();

        $this->assertFalse($event->isNearlyFull());
    }

    /** @test */
    public function it_calculates_capacity_percentage()
    {
        $event = Event::factory()->create(['max_seats' => 100]);
        Registration::factory()->for($event)->paid()->count(75)->create();

        $this->assertEquals(75.0, $event->capacityPercentage());
    }

    /** @test */
    public function it_returns_null_capacity_for_unlimited_events()
    {
        $event = Event::factory()->unlimited()->create();

        $this->assertNull($event->capacityPercentage());
    }

    /** @test */
    public function it_generates_api_key()
    {
        $event = Event::factory()->create();

        $apiKey = $event->generateApiKey();

        $this->assertStringStartsWith('evt_', $apiKey);
        $this->assertEquals(68, strlen($apiKey)); // evt_ + 64 hex chars

        $event->refresh();
        $this->assertEquals($apiKey, $event->api_key);
    }

    /** @test */
    public function it_generates_webhook_secret()
    {
        $event = Event::factory()->create();

        $secret = $event->generateWebhookSecret();

        $this->assertStringStartsWith('whsec_', $secret);
        $event->refresh();
        $this->assertEquals($secret, $event->webhook_secret);
    }

    /** @test */
    public function it_detects_webhook_configuration()
    {
        $event = Event::factory()->withWebhook()->create();

        $this->assertTrue($event->hasWebhook());
    }

    /** @test */
    public function it_detects_missing_webhook()
    {
        $event = Event::factory()->create();

        $this->assertFalse($event->hasWebhook());
    }

    /** @test */
    public function it_checks_if_webhook_event_is_enabled()
    {
        $event = Event::factory()->withWebhook()->create([
            'webhook_events' => ['registration.created', 'payment.succeeded'],
        ]);

        $this->assertTrue($event->webhookEnabled('registration.created'));
        $this->assertTrue($event->webhookEnabled('payment.succeeded'));
        $this->assertFalse($event->webhookEnabled('registration.cancelled'));
    }

    /** @test */
    public function it_uses_slug_as_route_key()
    {
        $event = Event::factory()->create(['slug' => 'my-event']);

        $this->assertEquals('slug', $event->getRouteKeyName());
    }

    /** @test */
    public function it_hides_sensitive_fields()
    {
        $event = Event::factory()->withApiKey()->withWebhook()->create();

        $array = $event->toArray();

        $this->assertArrayNotHasKey('api_key', $array);
        $this->assertArrayNotHasKey('webhook_secret', $array);
    }
}
