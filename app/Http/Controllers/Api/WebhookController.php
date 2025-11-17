<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\StripeCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private StripeCheckoutService $stripeService
    ) {
    }

    /**
     * Handle Stripe webhook
     *
     * POST /api/webhooks/stripe/{eventSlug?}
     * Note: eventSlug is optional and kept for backward compatibility
     */
    public function stripe(Request $request, ?string $eventSlug = null): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        if (!$sigHeader) {
            return response()->json([
                'success' => false,
                'message' => 'Missing Stripe signature header',
            ], 400);
        }

        try {
            // Use shared webhook handler (no event-specific secret needed)
            $result = $this->stripeService->handleWebhook($payload, $sigHeader);

            return response()->json([
                'success' => true,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'event_slug' => $eventSlug,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
