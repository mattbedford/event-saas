<?php

namespace Tests\Feature\Services;

use App\Events\RegistrationCompleted;
use App\Models\Coupon;
use App\Models\Event;
use App\Models\Registration;
use App\Services\CouponService;
use App\Services\RegistrationService;
use App\Services\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class RegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RegistrationService $service;
    private CouponService $couponService;
    private StripeCheckoutService $stripeService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Stripe service to avoid real API calls
        $this->stripeService = Mockery::mock(StripeCheckoutService::class);
        $this->app->instance(StripeCheckoutService::class, $this->stripeService);

        $this->couponService = app(CouponService::class);
        $this->service = app(RegistrationService::class);
    }

    /** @test */
    public function it_creates_registration_without_coupon()
    {
        $event = Event::factory()->create(['ticket_price' => 100]);

        $data = [
            'email' => 'test@example.com',
            'name' => 'John',
            'surname' => 'Doe',
            'company' => 'Acme Corp',
        ];

        $registration = $this->service->createRegistration($event, $data);

        $this->assertDatabaseHas('registrations', [
            'event_id' => $event->id,
            'email' => 'test@example.com',
            'name' => 'John',
            'surname' => 'Doe',
            'company' => 'Acme Corp',
            'expected_amount' => 100,
            'payment_status' => 'pending',
            'coupon_code' => null,
            'discount_amount' => 0,
        ]);

        $this->assertEquals(100, $registration->expected_amount);
        $this->assertEquals('pending', $registration->payment_status);
    }

    /** @test */
    public function it_creates_registration_with_percentage_coupon()
    {
        $event = Event::factory()->create(['ticket_price' => 100]);
        $coupon = Coupon::factory()
            ->for($event)
            ->percentage(20)
            ->create();

        $data = [
            'email' => 'test@example.com',
            'name' => 'John',
            'surname' => 'Doe',
            'coupon_code' => $coupon->code,
        ];

        $registration = $this->service->createRegistration($event, $data);

        $this->assertEquals($coupon->code, $registration->coupon_code);
        $this->assertEquals(20, $registration->discount_amount);
        $this->assertEquals(80, $registration->expected_amount);
        $this->assertEquals('pending', $registration->payment_status);

        // Verify coupon was used
        $coupon->refresh();
        $this->assertEquals(1, $coupon->used_count);
    }

    /** @test */
    public function it_creates_registration_with_fixed_coupon()
    {
        $event = Event::factory()->create(['ticket_price' => 100]);
        $coupon = Coupon::factory()
            ->for($event)
            ->fixed(30)
            ->create();

        $data = [
            'email' => 'test@example.com',
            'name' => 'John',
            'surname' => 'Doe',
            'coupon_code' => $coupon->code,
        ];

        $registration = $this->service->createRegistration($event, $data);

        $this->assertEquals(30, $registration->discount_amount);
        $this->assertEquals(70, $registration->expected_amount);
    }

    /** @test */
    public function it_creates_free_registration_with_100_percent_coupon()
    {
        EventFacade::fake([RegistrationCompleted::class]);

        $event = Event::factory()->create(['ticket_price' => 100]);
        $coupon = Coupon::factory()
            ->for($event)
            ->percentage(100)
            ->create();

        $data = [
            'email' => 'test@example.com',
            'name' => 'John',
            'surname' => 'Doe',
            'coupon_code' => $coupon->code,
        ];

        $registration = $this->service->createRegistration($event, $data);

        // Free registration should be marked as paid immediately
        $this->assertEquals('paid', $registration->payment_status);
        $this->assertEquals(0, $registration->expected_amount);
        $this->assertEquals(100, $registration->discount_amount);

        // Should trigger completion event
        EventFacade::assertDispatched(RegistrationCompleted::class);
    }

    /** @test */
    public function it_handles_invalid_coupon_gracefully()
    {
        $event = Event::factory()->create(['ticket_price' => 100]);

        $data = [
            'email' => 'test@example.com',
            'name' => 'John',
            'surname' => 'Doe',
            'coupon_code' => 'INVALID',
        ];

        $registration = $this->service->createRegistration($event, $data);

        // Should create registration without discount
        $this->assertNull($registration->coupon_code);
        $this->assertEquals(0, $registration->discount_amount);
        $this->assertEquals(100, $registration->expected_amount);
    }

    /** @test */
    public function it_refuses_exhausted_coupon()
    {
        $event = Event::factory()->create(['ticket_price' => 100]);
        $coupon = Coupon::factory()
            ->for($event)
            ->percentage(20)
            ->exhausted()
            ->create();

        $data = [
            'email' => 'test@example.com',
            'name' => 'John',
            'surname' => 'Doe',
            'coupon_code' => $coupon->code,
        ];

        $registration = $this->service->createRegistration($event, $data);

        // Should not apply exhausted coupon
        $this->assertNull($registration->coupon_code);
        $this->assertEquals(100, $registration->expected_amount);
    }

    /** @test */
    public function it_reserves_coupon_on_registration()
    {
        $event = Event::factory()->create(['ticket_price' => 100]);
        $coupon = Coupon::factory()
            ->for($event)
            ->singleUse()
            ->percentage(20)
            ->create();

        $data = [
            'email' => 'test@example.com',
            'name' => 'John',
            'surname' => 'Doe',
            'coupon_code' => $coupon->code,
        ];

        $this->service->createRegistration($event, $data);

        $coupon->refresh();

        // Should increment used_count
        $this->assertEquals(1, $coupon->used_count);
    }

    /** @test */
    public function it_completes_registration_and_triggers_event()
    {
        EventFacade::fake([RegistrationCompleted::class]);

        $registration = Registration::factory()
            ->paid()
            ->create();

        $this->service->completeRegistration($registration);

        EventFacade::assertDispatched(RegistrationCompleted::class, function ($event) use ($registration) {
            return $event->registration->id === $registration->id;
        });
    }

    /** @test */
    public function it_does_not_trigger_completion_event_for_pending_registration()
    {
        EventFacade::fake([RegistrationCompleted::class]);

        $registration = Registration::factory()->create([
            'payment_status' => 'pending',
        ]);

        $this->service->completeRegistration($registration);

        EventFacade::assertNotDispatched(RegistrationCompleted::class);
    }

    /** @test */
    public function it_cancels_registration_without_coupon()
    {
        $registration = Registration::factory()->create();

        $this->service->cancelRegistration($registration);

        $this->assertSoftDeleted('registrations', [
            'id' => $registration->id,
        ]);
    }

    /** @test */
    public function it_cancels_registration_and_releases_coupon()
    {
        $event = Event::factory()->create();
        $coupon = Coupon::factory()
            ->for($event)
            ->percentage(20)
            ->create(['used_count' => 1]);

        $registration = Registration::factory()
            ->for($event)
            ->withCoupon($coupon->code)
            ->create();

        $this->service->cancelRegistration($registration);

        // Coupon should be released
        $coupon->refresh();
        $this->assertEquals(0, $coupon->used_count);

        $this->assertSoftDeleted('registrations', [
            'id' => $registration->id,
        ]);
    }

    /** @test */
    public function it_refunds_registration()
    {
        $registration = Registration::factory()
            ->paid()
            ->create();

        $this->service->refundRegistration($registration);

        $registration->refresh();

        $this->assertEquals('refunded', $registration->payment_status);
    }

    /** @test */
    public function it_refunds_registration_and_releases_coupon()
    {
        $event = Event::factory()->create();
        $coupon = Coupon::factory()
            ->for($event)
            ->percentage(20)
            ->create(['used_count' => 1]);

        $registration = Registration::factory()
            ->for($event)
            ->paid()
            ->withCoupon($coupon->code)
            ->create();

        $this->service->refundRegistration($registration);

        // Coupon should be released
        $coupon->refresh();
        $this->assertEquals(0, $coupon->used_count);

        $registration->refresh();
        $this->assertEquals('refunded', $registration->payment_status);
    }

    /** @test */
    public function it_finds_registration_by_email_and_event()
    {
        $event = Event::factory()->create();
        $registration = Registration::factory()
            ->for($event)
            ->create(['email' => 'test@example.com']);

        // Create another registration for different event
        Registration::factory()->create(['email' => 'test@example.com']);

        $found = $this->service->findRegistration($event, 'test@example.com');

        $this->assertNotNull($found);
        $this->assertEquals($registration->id, $found->id);
    }

    /** @test */
    public function it_returns_null_when_registration_not_found()
    {
        $event = Event::factory()->create();

        $found = $this->service->findRegistration($event, 'nonexistent@example.com');

        $this->assertNull($found);
    }

    /** @test */
    public function it_checks_if_email_is_registered()
    {
        $event = Event::factory()->create();

        // Create paid registration
        Registration::factory()
            ->for($event)
            ->paid()
            ->create(['email' => 'registered@example.com']);

        // Create pending registration (should not count as registered)
        Registration::factory()
            ->for($event)
            ->create(['email' => 'pending@example.com', 'payment_status' => 'pending']);

        $this->assertTrue($this->service->isEmailRegistered($event, 'registered@example.com'));
        $this->assertFalse($this->service->isEmailRegistered($event, 'pending@example.com'));
        $this->assertFalse($this->service->isEmailRegistered($event, 'nonexistent@example.com'));
    }

    /** @test */
    public function it_detects_when_registration_needs_payment()
    {
        $paidRegistration = Registration::factory()->paid()->create();
        $pendingRegistration = Registration::factory()->create([
            'expected_amount' => 100,
            'payment_status' => 'pending',
        ]);
        $freeRegistration = Registration::factory()->create([
            'expected_amount' => 0,
            'payment_status' => 'paid',
        ]);

        $this->assertFalse($this->service->needsPayment($paidRegistration));
        $this->assertTrue($this->service->needsPayment($pendingRegistration));
        $this->assertFalse($this->service->needsPayment($freeRegistration));
    }

    /** @test */
    public function it_handles_concurrent_coupon_usage()
    {
        $event = Event::factory()->create(['ticket_price' => 100]);
        $coupon = Coupon::factory()
            ->for($event)
            ->singleUse()
            ->percentage(20)
            ->create();

        $data1 = [
            'email' => 'user1@example.com',
            'name' => 'User',
            'surname' => 'One',
            'coupon_code' => $coupon->code,
        ];

        $data2 = [
            'email' => 'user2@example.com',
            'name' => 'User',
            'surname' => 'Two',
            'coupon_code' => $coupon->code,
        ];

        // First registration should succeed
        $registration1 = $this->service->createRegistration($event, $data1);
        $this->assertEquals($coupon->code, $registration1->coupon_code);

        // Second registration should fail to apply coupon (exhausted)
        $registration2 = $this->service->createRegistration($event, $data2);
        $this->assertNull($registration2->coupon_code);
        $this->assertEquals(100, $registration2->expected_amount);
    }

    /** @test */
    public function it_logs_errors_when_coupon_operations_fail()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Failed to release coupon', Mockery::type('array'));

        Log::shouldReceive('info')->andReturn(null);

        $event = Event::factory()->create();

        // Create registration with invalid coupon reference
        $registration = Registration::factory()
            ->for($event)
            ->create(['coupon_code' => 'NONEXISTENT']);

        // This should log error but not throw
        $this->service->cancelRegistration($registration);

        $this->assertSoftDeleted('registrations', ['id' => $registration->id]);
    }

    /** @test */
    public function it_stores_additional_fields_in_registration()
    {
        $event = Event::factory()->create(['ticket_price' => 100]);

        $data = [
            'email' => 'test@example.com',
            'name' => 'John',
            'surname' => 'Doe',
            'additional_fields' => [
                'dietary_requirements' => 'Vegetarian',
                'special_needs' => 'Wheelchair access',
            ],
        ];

        $registration = $this->service->createRegistration($event, $data);

        $this->assertEquals([
            'dietary_requirements' => 'Vegetarian',
            'special_needs' => 'Wheelchair access',
        ], $registration->additional_fields);
    }

    /** @test */
    public function it_returns_latest_registration_when_multiple_exist()
    {
        $event = Event::factory()->create();

        // Create older registration
        $older = Registration::factory()
            ->for($event)
            ->create([
                'email' => 'test@example.com',
                'created_at' => now()->subDays(7),
            ]);

        // Create newer registration
        $newer = Registration::factory()
            ->for($event)
            ->create([
                'email' => 'test@example.com',
                'created_at' => now(),
            ]);

        $found = $this->service->findRegistration($event, 'test@example.com');

        $this->assertEquals($newer->id, $found->id);
    }
}
