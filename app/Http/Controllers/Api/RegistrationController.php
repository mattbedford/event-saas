<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Registration;
use App\Services\RegistrationService;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class RegistrationController extends Controller
{
    public function __construct(
        private RegistrationService $registrationService,
        private CouponService $couponService
    ) {
    }

    /**
     * Create a new registration
     *
     * POST /api/events/{eventSlug}/registrations
     */
    public function store(Request $request, string $eventSlug): JsonResponse
    {
        $event = Event::where('slug', $eventSlug)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'coupon_code' => 'nullable|string',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if email is already registered
        if ($this->registrationService->isEmailRegistered($event, $request->email)) {
            return response()->json([
                'success' => false,
                'message' => 'This email is already registered for this event',
            ], 409);
        }

        try {
            // Create registration
            $registration = $this->registrationService->createRegistration(
                $event,
                $request->only(['email', 'name', 'surname', 'company', 'phone', 'coupon_code'])
            );

            // Create Stripe checkout session
            $session = $this->registrationService->createCheckoutSession(
                $registration,
                $request->success_url,
                $request->cancel_url
            );

            return response()->json([
                'success' => true,
                'registration' => [
                    'id' => $registration->id,
                    'email' => $registration->email,
                    'name' => $registration->full_name,
                    'expected_amount' => $registration->expected_amount,
                    'discount_amount' => $registration->discount_amount,
                ],
                'checkout' => [
                    'session_id' => $session->id,
                    'url' => $session->url,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get registration by email
     *
     * GET /api/events/{eventSlug}/registrations/{email}
     */
    public function show(string $eventSlug, string $email): JsonResponse
    {
        $event = Event::where('slug', $eventSlug)->firstOrFail();
        $registration = $this->registrationService->findRegistration($event, $email);

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'registration' => [
                'id' => $registration->id,
                'email' => $registration->email,
                'name' => $registration->full_name,
                'company' => $registration->company,
                'payment_status' => $registration->payment_status,
                'paid_amount' => $registration->paid_amount,
                'expected_amount' => $registration->expected_amount,
                'badge_generated' => $registration->badge_generated,
                'created_at' => $registration->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Validate a coupon
     *
     * POST /api/events/{eventSlug}/validate-coupon
     */
    public function validateCoupon(Request $request, string $eventSlug): JsonResponse
    {
        $event = Event::where('slug', $eventSlug)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $pricing = $this->couponService->calculatePricing(
            $event,
            $request->coupon_code
        );

        if (isset($pricing['error'])) {
            return response()->json([
                'success' => false,
                'message' => $pricing['error'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'pricing' => $pricing,
        ]);
    }

    /**
     * Get event details
     *
     * GET /api/events/{eventSlug}
     */
    public function getEvent(string $eventSlug): JsonResponse
    {
        $event = Event::where('slug', $eventSlug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
                'ticket_price' => $event->ticket_price,
                'event_date' => $event->event_date->toIso8601String(),
                'is_active' => $event->is_active,
            ],
        ]);
    }
}
