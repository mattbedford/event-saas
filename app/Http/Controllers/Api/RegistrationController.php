<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Registration;
use App\Services\RegistrationService;
use App\Services\CouponService;
use App\Services\HubspotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

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

        // Check if registrations are enabled
        if (!($event->settings['registrations_enabled'] ?? true)) {
            $messageType = $event->settings['registration_status_message_type'] ?? 'closed';
            $customMessage = $event->settings['registration_status_message'] ?? null;

            $messages = [
                'not_open' => 'Registrations are not open yet. Please check back soon.',
                'closed' => 'Registrations for this event are now closed.',
                'sold_out' => 'Sorry, this event is sold out!',
                'custom' => $customMessage ?? 'Registrations are currently closed.',
            ];

            return response()->json([
                'success' => false,
                'message' => $messages[$messageType],
            ], 403);
        }

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

            $response = [
                'success' => true,
                'registration' => [
                    'id' => $registration->id,
                    'email' => $registration->email,
                    'name' => $registration->full_name,
                    'expected_amount' => $registration->expected_amount,
                    'discount_amount' => $registration->discount_amount,
                    'payment_status' => $registration->payment_status,
                ],
            ];

            // Check if payment is needed (i.e., not 100% discount)
            if ($this->registrationService->needsPayment($registration)) {
                // Create Stripe checkout session
                $session = $this->registrationService->createCheckoutSession(
                    $registration,
                    $request->success_url,
                    $request->cancel_url
                );

                $response['checkout'] = [
                    'session_id' => $session->id,
                    'url' => $session->url,
                ];
            } else {
                // 100% discount - redirect directly to success URL
                $response['redirect_url'] = $request->success_url;
                $response['message'] = 'Registration completed with 100% discount. No payment required.';
            }

            return response()->json($response);
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

    /**
     * Create an administrative registration (speakers, VIPs, comped attendees)
     *
     * POST /api/admin/registrations
     *
     * This endpoint allows external systems to add registrations programmatically.
     * Requires API token authentication via Bearer token in Authorization header.
     *
     * Use cases:
     * - Adding speakers to the event
     * - Comping VIP attendees
     * - Bulk importing registrations from other systems
     *
     * Example:
     * curl -X POST https://your-domain.com/api/admin/registrations \
     *   -H "Authorization: Bearer YOUR_API_TOKEN" \
     *   -H "Content-Type: application/json" \
     *   -d '{
     *     "event_slug": "web-summit-2025",
     *     "email": "speaker@example.com",
     *     "full_name": "Jane Doe",
     *     "company": "Tech Corp",
     *     "phone": "+41 123 456 789",
     *     "skip_payment": true,
     *     "notes": "Keynote speaker - comped ticket"
     *   }'
     */
    public function storeAdmin(Request $request): JsonResponse
    {
        // Validate API token
        $apiToken = $request->bearerToken();
        if (!$apiToken || $apiToken !== config('services.api_token')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - invalid API token',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'event_slug' => 'required|string|exists:events,slug',
            'email' => 'required|email',
            'full_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'coupon_code' => 'nullable|string',
            'skip_payment' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $event = Event::where('slug', $request->event_slug)->firstOrFail();

        // Check if email is already registered
        if ($this->registrationService->isEmailRegistered($event, $request->email)) {
            return response()->json([
                'success' => false,
                'message' => 'This email is already registered for this event',
                'registration' => Registration::where('event_id', $event->id)
                    ->where('email', $request->email)
                    ->first()
                    ->only(['id', 'email', 'full_name', 'payment_status']),
            ], 409);
        }

        try {
            // Create registration
            $registration = Registration::create([
                'event_id' => $event->id,
                'email' => $request->email,
                'full_name' => $request->full_name,
                'company' => $request->company,
                'phone' => $request->phone,
                'ticket_price' => $event->ticket_price,
                'discount_amount' => $request->skip_payment ? $event->ticket_price : 0,
                'paid_amount' => $request->skip_payment ? 0 : $event->ticket_price,
                'expected_amount' => $request->skip_payment ? 0 : $event->ticket_price,
                'coupon_code' => $request->coupon_code,
                'payment_status' => $request->skip_payment ? 'paid' : 'pending',
                'metadata' => [
                    'source' => 'admin_api',
                    'notes' => $request->notes,
                    'created_via' => 'api',
                ],
            ]);

            // Add to Hubspot if configured
            if ($event->hubspot_list_id) {
                try {
                    $hubspotService = app(HubspotService::class);
                    $hubspotService->addContactToList(
                        $registration->email,
                        $event->hubspot_list_id,
                        [
                            'firstname' => explode(' ', $registration->full_name)[0] ?? '',
                            'lastname' => explode(' ', $registration->full_name, 2)[1] ?? '',
                            'company' => $registration->company,
                            'phone' => $registration->phone,
                        ]
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to add admin registration to Hubspot', [
                        'registration_id' => $registration->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Registration created successfully',
                'registration' => [
                    'id' => $registration->id,
                    'email' => $registration->email,
                    'full_name' => $registration->full_name,
                    'company' => $registration->company,
                    'payment_status' => $registration->payment_status,
                    'paid_amount' => $registration->paid_amount,
                    'expected_amount' => $registration->expected_amount,
                    'created_at' => $registration->created_at->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Admin registration creation failed', [
                'event_slug' => $request->event_slug,
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create registration: ' . $e->getMessage(),
            ], 500);
        }
    }
}
