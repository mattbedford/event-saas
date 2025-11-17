<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\StripeCheckoutService;
use App\Services\BrevoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private StripeCheckoutService $stripeService,
        private BrevoService $brevoService
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

    /**
     * Handle Brevo webhook for email tracking
     *
     * POST /api/webhooks/brevo
     *
     * Brevo sends webhooks for various email events:
     * - request: Email successfully sent to Brevo
     * - delivered: Email delivered to recipient
     * - hard_bounce: Email bounced (invalid address)
     * - soft_bounce: Email temporarily bounced
     * - blocked: Email blocked by recipient server
     * - spam: Email marked as spam
     * - invalid_email: Email address is invalid
     * - deferred: Email delivery deferred
     * - click: Link in email was clicked
     * - opened: Email was opened
     * - unsubscribe: Recipient unsubscribed
     */
    public function brevo(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();

            Log::info('Brevo webhook received', [
                'event' => $payload['event'] ?? 'unknown',
                'email' => $payload['email'] ?? null,
                'message_id' => $payload['message-id'] ?? $payload['id'] ?? null,
            ]);

            // Process the webhook using BrevoService
            $this->brevoService->processWebhook($payload);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Brevo webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            // Return 200 even on error to prevent Brevo from retrying
            // We log the error for manual investigation
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 200);
        }
    }
}
