<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistrationService
{
    public function __construct(
        private CouponService $couponService,
        private StripeCheckoutService $stripeService,
    ) {
    }

    /**
     * Create a new registration
     */
    public function createRegistration(Event $event, array $data): Registration
    {
        return DB::transaction(function () use ($event, $data) {
            // Calculate pricing with coupon if provided
            $pricing = $this->couponService->calculatePricing(
                $event,
                $data['coupon_code'] ?? null
            );

            // Create the registration
            $registration = Registration::create([
                'event_id' => $event->id,
                'email' => $data['email'],
                'name' => $data['name'],
                'surname' => $data['surname'],
                'company' => $data['company'] ?? null,
                'phone' => $data['phone'] ?? null,
                'additional_fields' => $data['additional_fields'] ?? null,
                'expected_amount' => $pricing['final_price'],
                'paid_amount' => 0,
                'payment_status' => 'pending',
                'coupon_code' => $pricing['coupon_code'],
                'discount_amount' => $pricing['discount_amount'],
            ]);

            // If coupon was used, reserve it
            if ($pricing['coupon_code']) {
                try {
                    $coupon = $event->coupons()
                        ->byCode($pricing['coupon_code'])
                        ->first();

                    if ($coupon) {
                        $this->couponService->reserveCoupon($coupon);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to reserve coupon', [
                        'coupon_code' => $pricing['coupon_code'],
                        'error' => $e->getMessage(),
                    ]);
                    // Continue anyway - registration is created
                }
            }

            return $registration;
        });
    }

    /**
     * Create a Stripe checkout session for a registration
     */
    public function createCheckoutSession(
        Registration $registration,
        string $successUrl,
        string $cancelUrl
    ): \Stripe\Checkout\Session {
        return $this->stripeService->createCheckoutSession(
            $registration->event,
            $registration,
            $successUrl,
            $cancelUrl
        );
    }

    /**
     * Complete registration after successful payment
     */
    public function completeRegistration(Registration $registration): void
    {
        // Mark confirmation as needed (will be sent by queue job)
        if ($registration->isPaid() && !$registration->confirmation_sent) {
            // Trigger confirmation email (handled by event listener or queue)
            event(new \App\Events\RegistrationCompleted($registration));
        }

        Log::info('Registration completed', [
            'id' => $registration->id,
            'email' => $registration->email,
            'status' => $registration->payment_status,
        ]);
    }

    /**
     * Cancel a registration and release coupon
     */
    public function cancelRegistration(Registration $registration): void
    {
        DB::transaction(function () use ($registration) {
            // Release coupon if one was used
            if ($registration->coupon_code) {
                $coupon = $registration->event->coupons()
                    ->byCode($registration->coupon_code)
                    ->first();

                if ($coupon) {
                    $this->couponService->releaseCoupon($coupon);
                }
            }

            $registration->delete();

            Log::info('Registration cancelled', ['id' => $registration->id]);
        });
    }

    /**
     * Refund a registration
     */
    public function refundRegistration(Registration $registration): void
    {
        DB::transaction(function () use ($registration) {
            // Update payment status
            $registration->update(['payment_status' => 'refunded']);

            // Release coupon
            if ($registration->coupon_code) {
                $coupon = $registration->event->coupons()
                    ->byCode($registration->coupon_code)
                    ->first();

                if ($coupon) {
                    $this->couponService->releaseCoupon($coupon);
                }
            }

            Log::info('Registration refunded', ['id' => $registration->id]);
        });
    }

    /**
     * Get registration by email and event
     */
    public function findRegistration(Event $event, string $email): ?Registration
    {
        return Registration::where('event_id', $event->id)
            ->where('email', $email)
            ->latest()
            ->first();
    }

    /**
     * Check if email is already registered for event
     */
    public function isEmailRegistered(Event $event, string $email): bool
    {
        return Registration::where('event_id', $event->id)
            ->where('email', $email)
            ->whereIn('payment_status', ['paid', 'partial'])
            ->exists();
    }
}
