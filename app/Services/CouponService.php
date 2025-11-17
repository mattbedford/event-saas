<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponReservation;
use App\Models\Event;
use App\Models\Registration;
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
        // Find coupon by code (works for both event-specific and global coupons)
        $coupon = Coupon::byCode($code)->first();

        if (!$coupon) {
            throw new \Exception('Invalid coupon code');
        }

        // Use the new comprehensive validation method
        if (!$coupon->canBeUsedForEvent($event->id)) {
            // Provide specific error messages
            if (!$coupon->is_active) {
                throw new \Exception('This coupon is no longer active');
            }

            if ($coupon->isExpiredByYear()) {
                throw new \Exception('This coupon has expired (year: ' . $coupon->year . ')');
            }

            if ($coupon->valid_from && now()->lt($coupon->valid_from)) {
                throw new \Exception('This coupon is not yet valid');
            }

            if ($coupon->valid_until && now()->gt($coupon->valid_until)) {
                throw new \Exception('This coupon has expired');
            }

            // Check event scope
            if ($coupon->scope === 'event' && $coupon->event_id !== $event->id) {
                throw new \Exception('This coupon is not valid for this event');
            }

            // Check global limit
            if ($coupon->max_uses_global !== null) {
                $globalRemaining = $coupon->getRemainingUsesGlobal();
                if ($globalRemaining !== null && $globalRemaining <= 0) {
                    throw new \Exception('This coupon has reached its annual usage limit');
                }
            }

            // Check per-event limit
            if ($coupon->max_uses_per_event !== null) {
                $eventRemaining = $coupon->getRemainingUsesForEvent($event->id);
                if ($eventRemaining !== null && $eventRemaining <= 0) {
                    throw new \Exception('This coupon has reached its usage limit for this event');
                }
            }

            // Legacy max_uses check (backward compatibility)
            if ($coupon->max_uses && $coupon->used_count >= $coupon->max_uses) {
                throw new \Exception('This coupon has reached its usage limit');
            }

            throw new \Exception('This coupon is not valid for this event');
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

    /**
     * Create a soft reservation for a coupon
     * This doesn't count against usage limits yet, just reserves the coupon
     * Reservation expires in 30 minutes if not confirmed
     *
     * @param Coupon $coupon
     * @param Registration $registration
     * @param Event $event
     * @return CouponReservation
     * @throws \Exception
     */
    public function createReservation(Coupon $coupon, Registration $registration, Event $event): CouponReservation
    {
        return DB::transaction(function () use ($coupon, $registration, $event) {
            // Check if coupon can still be used (including active reservations)
            if (!$this->canReserveCoupon($coupon, $event->id)) {
                throw new \Exception('Coupon is no longer available');
            }

            // Release any existing reservation for this registration
            CouponReservation::where('registration_id', $registration->id)
                ->where('status', 'reserved')
                ->get()
                ->each(fn($res) => $res->release());

            // Create new reservation
            $reservation = CouponReservation::create([
                'coupon_id' => $coupon->id,
                'registration_id' => $registration->id,
                'event_id' => $event->id,
                'status' => 'reserved',
                'expires_at' => now()->addMinutes(30), // 30 minute timeout
            ]);

            return $reservation;
        });
    }

    /**
     * Confirm a coupon reservation (user completed payment/registration)
     * This is when we actually count it against usage limits
     *
     * @param CouponReservation $reservation
     * @return void
     */
    public function confirmReservation(CouponReservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            if ($reservation->status !== 'reserved') {
                throw new \Exception('Reservation already processed');
            }

            // Confirm the reservation
            $reservation->confirm();

            // Now increment the actual usage count
            $coupon = Coupon::lockForUpdate()->find($reservation->coupon_id);
            $coupon->incrementUsage();
        });
    }

    /**
     * Release a coupon reservation (user abandoned, changed coupon, or payment failed)
     *
     * @param CouponReservation $reservation
     * @return void
     */
    public function releaseReservation(CouponReservation $reservation): void
    {
        if ($reservation->status === 'reserved') {
            $reservation->release();
        }
    }

    /**
     * Check if a coupon can be reserved (accounting for active reservations)
     *
     * @param Coupon $coupon
     * @param int $eventId
     * @return bool
     */
    private function canReserveCoupon(Coupon $coupon, int $eventId): bool
    {
        // First check basic validity
        if (!$coupon->canBeUsedForEvent($eventId)) {
            return false;
        }

        // Count active reservations (not yet confirmed/released)
        $activeReservations = CouponReservation::where('coupon_id', $coupon->id)
            ->where('status', 'reserved')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

        // Check global limit including active reservations
        if ($coupon->max_uses_global !== null) {
            $totalUsed = $coupon->getActualUsesGlobal() + $activeReservations;
            if ($totalUsed >= $coupon->max_uses_global) {
                return false;
            }
        }

        // Check per-event limit including active reservations
        if ($coupon->max_uses_per_event !== null) {
            $eventReservations = CouponReservation::where('coupon_id', $coupon->id)
                ->where('event_id', $eventId)
                ->where('status', 'reserved')
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->count();

            $totalEventUsed = $coupon->getActualUsesForEvent($eventId) + $eventReservations;
            if ($totalEventUsed >= $coupon->max_uses_per_event) {
                return false;
            }
        }

        return true;
    }

    /**
     * Expire old reservations (cleanup job)
     *
     * @return int Number of reservations expired
     */
    public function expireOldReservations(): int
    {
        $expired = CouponReservation::expired()->get();

        foreach ($expired as $reservation) {
            $reservation->expire();
        }

        return $expired->count();
    }
}
