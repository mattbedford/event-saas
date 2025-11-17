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
     * POST /api/webhooks/stripe/{eventSlug}
     */
    public function stripe(Request $request, string $eventSlug): JsonResponse
    {
        $event = Event::where('slug', $eventSlug)->firstOrFail();

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        if (!$sigHeader) {
            return response()->json([
                'success' => false,
                'message' => 'Missing Stripe signature header',
            ], 400);
        }

        try {
            $result = $this->stripeService->handleWebhook($payload, $sigHeader, $event);

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
