<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Event;
use Illuminate\Support\Facades\DB;

class CouponService
{
    /**
     * Validate and retrieve a coupon for an event
     *
     * @throws \Exception
     */
    public function validateCoupon(Event $event, string $code): Coupon
    {
        $coupon = Coupon::forEvent($event->id)
            ->byCode($code)
            ->first();

        if (!$coupon) {
            throw new \Exception('Invalid coupon code');
        }

        if (!$coupon->isValid()) {
            if (!$coupon->is_active) {
                throw new \Exception('This coupon is no longer active');
            }

            if ($coupon->valid_from && now()->lt($coupon->valid_from)) {
                throw new \Exception('This coupon is not yet valid');
            }

            if ($coupon->valid_until && now()->gt($coupon->valid_until)) {
                throw new \Exception('This coupon has expired');
            }

            if ($coupon->max_uses && $coupon->used_count >= $coupon->max_uses) {
                throw new \Exception('This coupon has reached its usage limit');
            }

            throw new \Exception('This coupon is not valid');
        }

        return $coupon;
    }

    /**
     * Apply a coupon to a price and return the discounted amount and discount value
     */
    public function applyCoupon(Coupon $coupon, float $price): array
    {
        $discountAmount = $coupon->calculateDiscount($price);
        $finalPrice = $coupon->applyDiscount($price);

        return [
            'original_price' => $price,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'coupon_code' => $coupon->code,
        ];
    }

    /**
     * Reserve a coupon (increment usage) within a transaction
     * This should be called when creating a registration
     */
    public function reserveCoupon(Coupon $coupon): void
    {
        DB::transaction(function () use ($coupon) {
            // Lock the row to prevent race conditions
            $coupon = Coupon::lockForUpdate()->find($coupon->id);

            if (!$coupon->hasUsesRemaining()) {
                throw new \Exception('Coupon usage limit reached');
            }

            $coupon->incrementUsage();
        });
    }

    /**
     * Release a coupon (decrement usage) if registration is cancelled/refunded
     */
    public function releaseCoupon(Coupon $coupon): void
    {
        DB::transaction(function () use ($coupon) {
            $coupon = Coupon::lockForUpdate()->find($coupon->id);
            $coupon->decrementUsage();
        });
    }

    /**
     * Calculate pricing with optional coupon
     */
    public function calculatePricing(Event $event, ?string $couponCode = null): array
    {
        $basePrice = (float) $event->ticket_price;

        if (!$couponCode) {
            return [
                'base_price' => $basePrice,
                'discount_amount' => 0,
                'final_price' => $basePrice,
                'coupon_code' => null,
            ];
        }

        try {
            $coupon = $this->validateCoupon($event, $couponCode);
            return $this->applyCoupon($coupon, $basePrice);
        } catch (\Exception $e) {
            // Return full price if coupon is invalid
            return [
                'base_price' => $basePrice,
                'discount_amount' => 0,
                'final_price' => $basePrice,
                'coupon_code' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
