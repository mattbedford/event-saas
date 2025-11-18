<?php

namespace Tests\Feature\Services;

use App\Models\Coupon;
use App\Models\Event;
use App\Models\Registration;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponServiceTest extends TestCase
{
    use RefreshDatabase;

    private CouponService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CouponService::class);
    }

    /** @test */
    public function it_validates_active_coupon()
    {
        $event = Event::factory()->create();
        $coupon = Coupon::factory()->for($event)->percentage(20)->create();

        $validated = $this->service->validateCoupon($event, $coupon->code);

        $this->assertEquals($coupon->id, $validated->id);
    }

    /** @test */
    public function it_rejects_invalid_coupon_code()
    {
        $event = Event::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid coupon code');

        $this->service->validateCoupon($event, 'INVALID');
    }

    /** @test */
    public function it_rejects_inactive_coupon()
    {
        $event = Event::factory()->create();
        $coupon = Coupon::factory()->for($event)->inactive()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This coupon is no longer active');

        $this->service->validateCoupon($event, $coupon->code);
    }

    /** @test */
    public function it_rejects_expired_coupon()
    {
        $event = Event::factory()->create();
        $coupon = Coupon::factory()->for($event)->expired()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This coupon has expired');

        $this->service->validateCoupon($event, $coupon->code);
    }

    /** @test */
    public function it_rejects_not_yet_valid_coupon()
    {
        $event = Event::factory()->create();
        $coupon = Coupon::factory()->for($event)->notYetValid()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This coupon is not yet valid');

        $this->service->validateCoupon($event, $coupon->code);
    }

    /** @test */
    public function it_rejects_exhausted_coupon()
    {
        $event = Event::factory()->create();
        $coupon = Coupon::factory()->for($event)->exhausted()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This coupon has reached its usage limit');

        $this->service->validateCoupon($event, $coupon->code);
    }

    /** @test */
    public function it_applies_percentage_discount()
    {
        $coupon = Coupon::factory()->percentage(25)->make();

        $result = $this->service->applyCoupon($coupon, 100);

        $this->assertEquals(100, $result['original_price']);
        $this->assertEquals(25, $result['discount_amount']);
        $this->assertEquals(75, $result['final_price']);
        $this->assertEquals($coupon->code, $result['coupon_code']);
    }

    /** @test */
    public function it_applies_fixed_discount()
    {
        $coupon = Coupon::factory()->fixed(30)->make();

        $result = $this->service->applyCoupon($coupon, 100);

        $this->assertEquals(100, $result['original_price']);
        $this->assertEquals(30, $result['discount_amount']);
        $this->assertEquals(70, $result['final_price']);
    }

    /** @test */
    public function it_applies_100_percent_discount()
    {
        $coupon = Coupon::factory()->percentage(100)->make();

        $result = $this->service->applyCoupon($coupon, 100);

        $this->assertEquals(100, $result['discount_amount']);
        $this->assertEquals(0, $result['final_price']);
    }

    /** @test */
    public function it_prevents_negative_price()
    {
        $coupon = Coupon::factory()->fixed(150)->make();

        $result = $this->service->applyCoupon($coupon, 100);

        // Should not go below zero
        $this->assertEquals(0, $result['final_price']);
    }

    /** @test */
    public function it_reserves_coupon()
    {
        $coupon = Coupon::factory()->create(['used_count' => 0]);

        $this->service->reserveCoupon($coupon);

        $coupon->refresh();
        $this->assertEquals(1, $coupon->used_count);
    }

    /** @test */
    public function it_prevents_reserving_exhausted_coupon()
    {
        $coupon = Coupon::factory()->exhausted()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Coupon usage limit reached');

        $this->service->reserveCoupon($coupon);
    }

    /** @test */
    public function it_handles_concurrent_coupon_reservations()
    {
        $coupon = Coupon::factory()->singleUse()->create();

        // First reservation should succeed
        $this->service->reserveCoupon($coupon);

        // Second should fail (exhausted)
        $this->expectException(\Exception::class);

        $coupon->refresh(); // Reload to get updated used_count
        $this->service->reserveCoupon($coupon);
    }

    /** @test */
    public function it_releases_coupon()
    {
        $coupon = Coupon::factory()->create(['used_count' => 3]);

        $this->service->releaseCoupon($coupon);

        $coupon->refresh();
        $this->assertEquals(2, $coupon->used_count);
    }

    /** @test */
    public function it_does_not_release_below_zero()
    {
        $coupon = Coupon::factory()->create(['used_count' => 0]);

        $this->service->releaseCoupon($coupon);

        $coupon->refresh();
        $this->assertEquals(0, $coupon->used_count);
    }

    /** @test */
    public function it_calculates_pricing_without_coupon()
    {
        $event = Event::factory()->create(['ticket_price' => 100]);

        $pricing = $this->service->calculatePricing($event);

        $this->assertEquals(100, $pricing['base_price']);
        $this->assertEquals(0, $pricing['discount_amount']);
        $this->assertEquals(100, $pricing['final_price']);
        $this->assertNull($pricing['coupon_code']);
    }

    /** @test */
    public function it_calculates_pricing_with_valid_coupon()
    {
        $event = Event::factory()->create(['ticket_price' => 100]);
        $coupon = Coupon::factory()->for($event)->percentage(20)->create();

        $pricing = $this->service->calculatePricing($event, $coupon->code);

        $this->assertEquals(20, $pricing['discount_amount']);
        $this->assertEquals(80, $pricing['final_price']);
        $this->assertEquals($coupon->code, $pricing['coupon_code']);
    }

    /** @test */
    public function it_returns_full_price_for_invalid_coupon()
    {
        $event = Event::factory()->create(['ticket_price' => 100]);

        $pricing = $this->service->calculatePricing($event, 'INVALID');

        $this->assertEquals(100, $pricing['final_price']);
        $this->assertNull($pricing['coupon_code']);
        $this->assertArrayHasKey('error', $pricing);
    }

    /** @test */
    public function it_returns_full_price_for_expired_coupon()
    {
        $event = Event::factory()->create(['ticket_price' => 100]);
        $coupon = Coupon::factory()->for($event)->expired()->create();

        $pricing = $this->service->calculatePricing($event, $coupon->code);

        $this->assertEquals(100, $pricing['final_price']);
        $this->assertNull($pricing['coupon_code']);
        $this->assertArrayHasKey('error', $pricing);
    }

    /** @test */
    public function it_validates_coupon_within_validity_period()
    {
        $event = Event::factory()->create();
        $coupon = Coupon::factory()
            ->for($event)
            ->withValidityPeriod()
            ->create();

        $validated = $this->service->validateCoupon($event, $coupon->code);

        $this->assertEquals($coupon->id, $validated->id);
    }

    /** @test */
    public function it_handles_coupon_with_no_expiry()
    {
        $event = Event::factory()->create();
        $coupon = Coupon::factory()
            ->for($event)
            ->create([
                'valid_from' => null,
                'valid_until' => null,
            ]);

        $validated = $this->service->validateCoupon($event, $coupon->code);

        $this->assertEquals($coupon->id, $validated->id);
    }

    /** @test */
    public function it_allows_multiple_uses_within_limit()
    {
        $coupon = Coupon::factory()->create([
            'max_uses' => 5,
            'used_count' => 0,
        ]);

        // Reserve 3 times
        $this->service->reserveCoupon($coupon);
        $coupon->refresh();
        $this->service->reserveCoupon($coupon);
        $coupon->refresh();
        $this->service->reserveCoupon($coupon);

        $coupon->refresh();
        $this->assertEquals(3, $coupon->used_count);
    }

    /** @test */
    public function it_prevents_exceeding_max_uses()
    {
        $coupon = Coupon::factory()->create([
            'max_uses' => 2,
            'used_count' => 0,
        ]);

        // Use twice
        $this->service->reserveCoupon($coupon);
        $coupon->refresh();
        $this->service->reserveCoupon($coupon);

        // Third should fail
        $this->expectException(\Exception::class);

        $coupon->refresh();
        $this->service->reserveCoupon($coupon);
    }

    /** @test */
    public function it_calculates_correct_discount_for_edge_cases()
    {
        // Test rounding
        $coupon = Coupon::factory()->percentage(33.33)->make();
        $result = $this->service->applyCoupon($coupon, 100);

        $this->assertEquals(33.33, $result['discount_amount']);
        $this->assertEquals(66.67, $result['final_price']);
    }

    /** @test */
    public function it_handles_zero_price()
    {
        $coupon = Coupon::factory()->percentage(20)->make();

        $result = $this->service->applyCoupon($coupon, 0);

        $this->assertEquals(0, $result['discount_amount']);
        $this->assertEquals(0, $result['final_price']);
    }

    /** @test */
    public function it_validates_coupons_for_specific_events_only()
    {
        $event1 = Event::factory()->create();
        $event2 = Event::factory()->create();

        $coupon = Coupon::factory()->for($event1)->create();

        // Should work for event1
        $validated = $this->service->validateCoupon($event1, $coupon->code);
        $this->assertEquals($coupon->id, $validated->id);

        // Should fail for event2
        $this->expectException(\Exception::class);
        $this->service->validateCoupon($event2, $coupon->code);
    }
}
