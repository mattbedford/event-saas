<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Registration;
use App\Services\CouponService;
use App\Services\HubspotService;
use App\Services\RegistrationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function __construct(
        private CouponService $couponService,
        private HubspotService $hubspotService,
        private RegistrationService $registrationService
    ) {
    }

    /**
     * Step 1: Validate coupon and return pricing
     * Called when user enters coupon code before submitting
     *
     * POST /api/events/{eventSlug}/checkout/validate
     */
    public function validateCoupon(Request $request, string $eventSlug): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $event = Event::where('slug', $eventSlug)->firstOrFail();

        try {
            $coupon = $this->couponService->validateCoupon($event, $request->coupon_code);
            $pricing = $this->couponService->applyCoupon($coupon, (float) $event->ticket_price);

            return response()->json([
                'success' => true,
                'valid' => true,
                'coupon' => [
                    'code' => $coupon->code,
                    'type' => $coupon->getTypeLabel(),
                    'discount_type' => $coupon->discount_type,
                    'discount_value' => $coupon->discount_value,
                ],
                'pricing' => [
                    'original_price' => $pricing['original_price'],
                    'discount_amount' => $pricing['discount_amount'],
                    'final_price' => $pricing['final_price'],
                    'is_free' => $pricing['final_price'] == 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'valid' => false,
                'message' => $e->getMessage(),
                'pricing' => [
                    'original_price' => (float) $event->ticket_price,
                    'discount_amount' => 0,
                    'final_price' => (float) $event->ticket_price,
                    'is_free' => false,
                ],
            ]);
        }
    }

    /**
     * Step 2: Initiate registration (create/update draft)
     * Called when user fills form and clicks "Register"
     * Creates draft registration, reserves coupon (soft), sends to Hubspot as prospect
     *
     * POST /api/events/{eventSlug}/checkout/initiate
     */
    public function initiateRegistration(Request $request, string $eventSlug): JsonResponse
    {
        $event = Event::where('slug', $eventSlug)->firstOrFail();

        // Check if registrations are enabled
        if (!($event->settings['registrations_enabled'] ?? true)) {
            return response()->json([
                'success' => false,
                'message' => 'Registrations for this event are closed.',
            ], 403);
        }

        // Check event capacity
        if (!$event->hasAvailableSeats()) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, this event is sold out!',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'coupon_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Check for existing registration
            $existingRegistration = Registration::where('event_id', $event->id)
                ->where('email', $request->email)
                ->first();

            // If confirmed registration exists, block
            if ($existingRegistration && $existingRegistration->isConfirmed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email is already registered for this event.',
                ], 409);
            }

            // If incomplete registration exists, update it
            if ($existingRegistration && $existingRegistration->canBeRetried()) {
                // Release old coupon reservation if exists
                if ($oldReservation = $existingRegistration->couponReservation) {
                    $this->couponService->releaseReservation($oldReservation);
                }

                // Update existing registration
                $existingRegistration->update([
                    'name' => $request->name,
                    'surname' => $request->surname,
                    'company' => $request->company,
                    'phone' => $request->phone,
                    'coupon_code' => $request->coupon_code,
                    'registration_status' => 'draft',
                ]);

                $registration = $existingRegistration;
            } else {
                // Create new draft registration
                $registration = Registration::create([
                    'event_id' => $event->id,
                    'email' => $request->email,
                    'name' => $request->name,
                    'surname' => $request->surname,
                    'company' => $request->company,
                    'phone' => $request->phone,
                    'coupon_code' => $request->coupon_code,
                    'expected_amount' => $event->ticket_price, // Will be updated after coupon validation
                    'payment_status' => 'pending',
                    'registration_status' => 'draft',
                    'attendance_status' => 'registered',
                ]);
            }

            // Calculate pricing and reserve coupon if provided
            $pricing = ['original_price' => (float) $event->ticket_price, 'discount_amount' => 0, 'final_price' => (float) $event->ticket_price];
            $couponReservation = null;

            if ($request->coupon_code) {
                try {
                    $coupon = $this->couponService->validateCoupon($event, $request->coupon_code);
                    $pricing = $this->couponService->applyCoupon($coupon, (float) $event->ticket_price);

                    // Create soft reservation (doesn't count against limits yet)
                    $couponReservation = $this->couponService->createReservation($coupon, $registration, $event);

                    // Update registration with pricing
                    $registration->update([
                        'discount_amount' => $pricing['discount_amount'],
                        'expected_amount' => $pricing['final_price'],
                    ]);
                } catch (\Exception $e) {
                    // Coupon invalid, proceed without it
                    Log::warning('Coupon validation failed during initiation', [
                        'registration_id' => $registration->id,
                        'coupon_code' => $request->coupon_code,
                        'error' => $e->getMessage(),
                    ]);

                    $registration->update([
                        'coupon_code' => null,
                        'discount_amount' => 0,
                        'expected_amount' => $event->ticket_price,
                    ]);
                }
            } else {
                $registration->update([
                    'discount_amount' => 0,
                    'expected_amount' => $event->ticket_price,
                ]);
            }

            // Send to Hubspot as prospect (not confirmed yet)
            if ($event->hubspot_list_id) {
                try {
                    $this->hubspotService->addContactToList(
                        $registration->email,
                        $event->hubspot_list_id,
                        [
                            'firstname' => $registration->name,
                            'lastname' => $registration->surname,
                            'company' => $registration->company,
                            'phone' => $registration->phone,
                            'registration_status' => 'draft',
                        ]
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to add draft registration to Hubspot', [
                        'registration_id' => $registration->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'registration' => [
                    'id' => $registration->id,
                    'email' => $registration->email,
                    'name' => $registration->full_name,
                    'status' => $registration->registration_status,
                ],
                'pricing' => $pricing,
                'coupon_reserved' => $couponReservation !== null,
                'reservation_expires_at' => $couponReservation?->expires_at?->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Registration initiation failed', [
                'event_slug' => $eventSlug,
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate registration: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Step 3: Complete registration
     * Called after user confirms they want to proceed
     * - If 100% coupon: Confirms immediately
     * - If payment needed: Creates Stripe session
     *
     * POST /api/events/{eventSlug}/checkout/complete
     */
    public function completeRegistration(Request $request, string $eventSlug): JsonResponse
    {
        $event = Event::where('slug', $eventSlug)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'registration_id' => 'required|exists:registrations,id',
            'success_url' => 'required_if:needs_payment,true|url',
            'cancel_url' => 'required_if:needs_payment,true|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $registration = Registration::findOrFail($request->registration_id);

        // Verify registration belongs to this event
        if ($registration->event_id !== $event->id) {
            return response()->json([
                'success' => false,
                'message' => 'Registration does not belong to this event.',
            ], 400);
        }

        try {
            // Check if payment is needed
            $needsPayment = $registration->expected_amount > 0;

            if (!$needsPayment) {
                // 100% discount - confirm immediately
                $registration->update([
                    'payment_status' => 'paid',
                    'paid_amount' => 0,
                    'registration_status' => 'confirmed',
                    'confirmed_at' => now(),
                ]);

                // Confirm coupon reservation
                if ($reservation = $registration->couponReservation) {
                    $this->couponService->confirmReservation($reservation);
                }

                // Update Hubspot status
                if ($event->hubspot_list_id) {
                    try {
                        $this->hubspotService->addContactToList(
                            $registration->email,
                            $event->hubspot_list_id,
                            ['registration_status' => 'confirmed']
                        );
                    } catch (\Exception $e) {
                        Log::warning('Failed to update Hubspot', ['error' => $e->getMessage()]);
                    }
                }

                return response()->json([
                    'success' => true,
                    'confirmed' => true,
                    'message' => 'Registration confirmed! No payment required.',
                    'registration' => [
                        'id' => $registration->id,
                        'status' => 'confirmed',
                        'payment_status' => 'paid',
                    ],
                ]);
            }

            // Payment needed - create Stripe session
            $registration->update([
                'registration_status' => 'pending_payment',
            ]);

            $session = $this->registrationService->createCheckoutSession(
                $registration,
                $request->success_url,
                $request->cancel_url
            );

            $registration->update([
                'stripe_session_id' => $session->id,
            ]);

            return response()->json([
                'success' => true,
                'confirmed' => false,
                'needs_payment' => true,
                'checkout' => [
                    'session_id' => $session->id,
                    'url' => $session->url,
                ],
                'registration' => [
                    'id' => $registration->id,
                    'status' => 'pending_payment',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Registration completion failed', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete registration: ' . $e->getMessage(),
            ], 500);
        }
    }
}
