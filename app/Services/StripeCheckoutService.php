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
    /**
     * Create a Stripe Checkout Session for a registration
     */
    public function createCheckoutSession(
        Event $event,
        Registration $registration,
        string $successUrl,
        string $cancelUrl
    ): StripeSession {
        // Set Stripe API key for this event
        Stripe::setApiKey($event->stripe_secret_key);

        $sessionData = [
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd', // TODO: Make this configurable per event
                    'product_data' => [
                        'name' => $event->name . ' - Registration',
                        'description' => "Registration for {$registration->full_name}",
                    ],
                    'unit_amount' => (int) ($registration->expected_amount * 100), // Stripe uses cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $registration->id,
            'customer_email' => $registration->email,
            'metadata' => [
                'event_id' => $event->id,
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
    public function handleWebhook(string $payload, string $sigHeader, Event $event): array
    {
        Stripe::setApiKey($event->stripe_secret_key);

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $event->stripe_webhook_secret
            );
        } catch (\Exception $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Webhook signature verification failed');
        }

        $response = ['handled' => false];

        switch ($event->type) {
            case 'checkout.session.completed':
                $response = $this->handleCheckoutCompleted($event->data->object);
                break;

            case 'payment_intent.succeeded':
                $response = $this->handlePaymentSucceeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $response = $this->handlePaymentFailed($event->data->object);
                break;

            case 'payment_intent.partially_funded':
                $response = $this->handlePartialPayment($event->data->object);
                break;

            default:
                Log::info('Unhandled Stripe webhook event', ['type' => $event->type]);
        }

        return $response;
    }

    /**
     * Handle checkout session completed
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

        // Get the payment intent to check actual amount paid
        $paymentIntentId = $session->payment_intent;

        if ($paymentIntentId) {
            Stripe::setApiKey($registration->event->stripe_secret_key);
            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);

            $amountPaid = $paymentIntent->amount / 100; // Convert from cents

            // CRITICAL: Always update payment status based on amount actually paid
            $registration->markAsPaid($amountPaid);
            $registration->update([
                'stripe_payment_intent_id' => $paymentIntentId,
            ]);

            Log::info('Payment completed', [
                'registration_id' => $registration->id,
                'amount_paid' => $amountPaid,
                'expected_amount' => $registration->expected_amount,
                'status' => $registration->payment_status,
            ]);

            return [
                'handled' => true,
                'registration_id' => $registration->id,
                'amount_paid' => $amountPaid,
                'status' => $registration->payment_status,
            ];
        }

        return ['handled' => false, 'error' => 'No payment intent'];
    }

    /**
     * Handle successful payment
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

        Log::info('Payment intent succeeded', [
            'registration_id' => $registration->id,
            'amount' => $amountPaid,
        ]);

        return [
            'handled' => true,
            'registration_id' => $registration->id,
            'amount_paid' => $amountPaid,
        ];
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailed($paymentIntent): array
    {
        $registrationId = $paymentIntent->metadata->registration_id ?? null;

        if (!$registrationId) {
            return ['handled' => false];
        }

        $registration = Registration::find($registrationId);

        if ($registration) {
            $registration->update(['payment_status' => 'failed']);

            Log::warning('Payment failed', [
                'registration_id' => $registration->id,
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
    public function getSession(Event $event, string $sessionId): StripeSession
    {
        Stripe::setApiKey($event->stripe_secret_key);
        return StripeSession::retrieve($sessionId);
    }
}
