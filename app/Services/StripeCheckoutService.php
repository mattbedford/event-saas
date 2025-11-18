<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Registration;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Webhook;
use Illuminate\Support\Facades\Log;

class StripeCheckoutService
{
    public function __construct(
        private CouponService $couponService,
        private HubspotService $hubspotService
    ) {
        // Initialize Stripe API key once in constructor
        Stripe::setApiKey(config('services.stripe.secret_key'));
    }

    /**
     * Create a Stripe Checkout Session for a registration
     */
    public function createCheckoutSession(
        Event $event,
        Registration $registration,
        string $successUrl,
        string $cancelUrl
    ): StripeSession {

        $lineItemData = [
            'quantity' => 1,
        ];

        $currency = config('services.stripe.currency', 'chf');

        // Use event's Stripe Product ID if available, otherwise create inline price data
        if ($event->stripe_product_id) {
            // Create a price for the existing product
            $price = \Stripe\Price::create([
                'product' => $event->stripe_product_id,
                'unit_amount' => $this->toStripeAmount($registration->expected_amount),
                'currency' => $currency,
            ]);
            $lineItemData['price'] = $price->id;
        } else {
            // Fallback to inline price data
            $lineItemData['price_data'] = [
                'currency' => $currency,
                'product_data' => [
                    'name' => $event->name . ' - Registration',
                    'description' => "Registration for {$registration->full_name}",
                ],
                'unit_amount' => $this->toStripeAmount($registration->expected_amount),
            ];
        }

        $sessionData = [
            'payment_method_types' => config('services.stripe.payment_methods', ['card']),
            'line_items' => [$lineItemData],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $registration->id,
            'customer_email' => $registration->email,
            'metadata' => [
                'event_id' => $event->id,
                'event_slug' => $event->slug,
                'registration_id' => $registration->id,
                'coupon_code' => $registration->coupon_code ?? '',
            ],
            // Allow partial payments if needed
            'payment_intent_data' => [
                'metadata' => [
                    'event_id' => $event->id,
                    'registration_id' => $registration->id,
                ],
            ],
        ];

        $session = StripeSession::create($sessionData);

        // Store the session ID
        $registration->update([
            'stripe_session_id' => $session->id,
        ]);

        return $session;
    }

    /**
     * Handle Stripe webhook events
     * CRITICAL: This prevents the partial payment status bug!
     */
    public function handleWebhook(string $payload, string $sigHeader): array
    {

        try {
            $stripeEvent = Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Webhook signature verification failed');
        }

        $response = ['handled' => false];

        switch ($stripeEvent->type) {
            case 'checkout.session.completed':
                $response = $this->handleCheckoutCompleted($stripeEvent->data->object);
                break;

            case 'checkout.session.expired':
                $response = $this->handleCheckoutExpired($stripeEvent->data->object);
                break;

            case 'payment_intent.succeeded':
                $response = $this->handlePaymentSucceeded($stripeEvent->data->object);
                break;

            case 'payment_intent.payment_failed':
                $response = $this->handlePaymentFailed($stripeEvent->data->object);
                break;

            case 'payment_intent.partially_funded':
                $response = $this->handlePartialPayment($stripeEvent->data->object);
                break;

            default:
                Log::info('Unhandled Stripe webhook event', ['type' => $stripeEvent->type]);
        }

        return $response;
    }

    /**
     * Handle checkout session completed
     * User finished Stripe checkout form - payment is processing
     */
    private function handleCheckoutCompleted($session): array
    {
        $registrationId = $session->metadata->registration_id ?? $session->client_reference_id;

        if (!$registrationId) {
            Log::error('No registration ID in Stripe session', ['session' => $session->id]);
            return ['handled' => false, 'error' => 'No registration ID'];
        }

        $registration = Registration::find($registrationId);

        if (!$registration) {
            Log::error('Registration not found', ['id' => $registrationId]);
            return ['handled' => false, 'error' => 'Registration not found'];
        }

        // Update to payment_processing state (user completed Stripe, awaiting payment confirmation)
        $registration->update([
            'registration_status' => 'payment_processing',
            'stripe_session_id' => $session->id,
        ]);

        // Get the payment intent to check actual amount paid
        $paymentIntentId = $session->payment_intent;

        if ($paymentIntentId) {
            $registration->update([
                'stripe_payment_intent_id' => $paymentIntentId,
            ]);

            Log::info('Checkout session completed - payment processing', [
                'registration_id' => $registration->id,
                'registration_status' => 'payment_processing',
                'payment_intent_id' => $paymentIntentId,
            ]);

            return [
                'handled' => true,
                'registration_id' => $registration->id,
                'status' => 'payment_processing',
            ];
        }

        return ['handled' => false, 'error' => 'No payment intent'];
    }

    /**
     * Handle checkout session expired
     * User abandoned checkout - release coupon reservation
     */
    private function handleCheckoutExpired($session): array
    {
        $registrationId = $session->metadata->registration_id ?? $session->client_reference_id;

        if (!$registrationId) {
            return ['handled' => false, 'error' => 'No registration ID'];
        }

        $registration = Registration::find($registrationId);

        if (!$registration) {
            return ['handled' => false, 'error' => 'Registration not found'];
        }

        // Mark as abandoned
        $registration->update([
            'registration_status' => 'abandoned',
        ]);

        // Release coupon reservation
        if ($reservation = $registration->couponReservation) {
            try {
                $this->couponService->releaseReservation($reservation);
                Log::info('Coupon reservation released after checkout expired', [
                    'registration_id' => $registration->id,
                    'coupon_code' => $registration->coupon_code,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to release coupon reservation', [
                    'registration_id' => $registration->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Checkout session expired - registration abandoned', [
            'registration_id' => $registration->id,
            'registration_status' => 'abandoned',
        ]);

        return [
            'handled' => true,
            'registration_id' => $registration->id,
            'status' => 'abandoned',
        ];
    }

    /**
     * Handle successful payment
     * Payment confirmed - finalize registration
     */
    private function handlePaymentSucceeded($paymentIntent): array
    {
        $registrationId = $paymentIntent->metadata->registration_id ?? null;

        if (!$registrationId) {
            return ['handled' => false, 'error' => 'No registration ID'];
        }

        $registration = Registration::find($registrationId);

        if (!$registration) {
            return ['handled' => false, 'error' => 'Registration not found'];
        }

        $amountPaid = $paymentIntent->amount / 100;

        // CRITICAL: Update payment status based on actual amount
        $registration->markAsPaid($amountPaid);

        // Mark registration as confirmed
        $registration->update([
            'registration_status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        // Confirm coupon reservation (NOW it counts against usage limits)
        if ($reservation = $registration->couponReservation) {
            try {
                $this->couponService->confirmReservation($reservation);
                Log::info('Coupon reservation confirmed', [
                    'registration_id' => $registration->id,
                    'coupon_code' => $registration->coupon_code,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to confirm coupon reservation', [
                    'registration_id' => $registration->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update Hubspot with confirmed status
        if ($registration->event->hubspot_list_id) {
            try {
                $this->hubspotService->addContactToList(
                    $registration->email,
                    $registration->event->hubspot_list_id,
                    ['registration_status' => 'confirmed']
                );
            } catch (\Exception $e) {
                Log::warning('Failed to update Hubspot', [
                    'registration_id' => $registration->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Payment succeeded - registration confirmed', [
            'registration_id' => $registration->id,
            'amount' => $amountPaid,
            'payment_status' => $registration->payment_status,
            'registration_status' => 'confirmed',
        ]);

        return [
            'handled' => true,
            'registration_id' => $registration->id,
            'amount_paid' => $amountPaid,
            'status' => 'confirmed',
        ];
    }

    /**
     * Handle failed payment
     * Release coupon reservation to allow retry
     */
    private function handlePaymentFailed($paymentIntent): array
    {
        $registrationId = $paymentIntent->metadata->registration_id ?? null;

        if (!$registrationId) {
            return ['handled' => false];
        }

        $registration = Registration::find($registrationId);

        if ($registration) {
            // Update status to payment_failed (allows retry)
            $registration->update([
                'payment_status' => 'failed',
                'registration_status' => 'payment_failed',
            ]);

            // Release coupon reservation so user can retry
            if ($reservation = $registration->couponReservation) {
                try {
                    $this->couponService->releaseReservation($reservation);
                    Log::info('Coupon reservation released after payment failure', [
                        'registration_id' => $registration->id,
                        'coupon_code' => $registration->coupon_code,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to release coupon reservation', [
                        'registration_id' => $registration->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::warning('Payment failed - registration can be retried', [
                'registration_id' => $registration->id,
                'registration_status' => 'payment_failed',
                'reason' => $paymentIntent->last_payment_error->message ?? 'Unknown',
            ]);
        }

        return ['handled' => true, 'registration_id' => $registrationId];
    }

    /**
     * Handle partial payment (important!)
     */
    private function handlePartialPayment($paymentIntent): array
    {
        $registrationId = $paymentIntent->metadata->registration_id ?? null;

        if (!$registrationId) {
            return ['handled' => false];
        }

        $registration = Registration::find($registrationId);

        if ($registration) {
            $amountPaid = ($paymentIntent->amount_received ?? 0) / 100;

            // CRITICAL: Mark as partial payment with correct amount
            $registration->markAsPaid($amountPaid);

            Log::warning('Partial payment received', [
                'registration_id' => $registration->id,
                'amount_paid' => $amountPaid,
                'expected' => $registration->expected_amount,
            ]);
        }

        return ['handled' => true, 'registration_id' => $registrationId];
    }

    /**
     * Retrieve a checkout session
     */
    public function getSession(string $sessionId): StripeSession
    {
        return StripeSession::retrieve($sessionId);
    }

    /**
     * Convert amount to Stripe format (cents)
     */
    private function toStripeAmount(float $amount): int
    {
        return (int) ($amount * 100);
    }

    /**
     * Convert Stripe amount (cents) to decimal
     */
    private function fromStripeAmount(int $amount): float
    {
        return $amount / 100;
    }
}
