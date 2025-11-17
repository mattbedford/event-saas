<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    /**
     * Validate a coupon code (public endpoint for external sites)
     *
     * GET /api/coupons/validate?code=ACME-2025
     *
     * This endpoint allows external sites (photo galleries, report access, etc.)
     * to validate coupon codes without authentication.
     *
     * Example use case:
     * - Photo gallery site checks if user's code is valid before granting access
     * - Report download site validates code before allowing download
     * - Partner sites verify code for gated content
     *
     * Returns coupon details including validity, company info, and usage stats.
     */
    public function validate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'message' => 'Coupon code is required',
                'errors' => $validator->errors(),
            ], 422);
        }

        $code = strtoupper($request->input('code'));

        // Find coupon
        $coupon = Coupon::where('code', $code)
            ->with('event')
            ->first();

        if (!$coupon) {
            return response()->json([
                'valid' => false,
                'message' => 'Coupon code not found',
            ], 404);
        }

        // Check if active
        if (!$coupon->is_active) {
            return response()->json([
                'valid' => false,
                'message' => 'This coupon is no longer active',
                'coupon' => [
                    'code' => $coupon->code,
                    'company' => $coupon->company_name,
                ],
            ]);
        }

        // Check if expired by year
        if ($coupon->isExpiredByYear()) {
            return response()->json([
                'valid' => false,
                'message' => 'This coupon has expired',
                'coupon' => [
                    'code' => $coupon->code,
                    'company' => $coupon->company_name,
                    'year' => $coupon->year,
                ],
            ]);
        }

        // Check usage limits
        $remainingUses = $coupon->remaining_uses;
        if ($remainingUses !== null && $remainingUses <= 0) {
            return response()->json([
                'valid' => false,
                'message' => 'This coupon has reached its maximum usage limit',
                'coupon' => [
                    'code' => $coupon->code,
                    'company' => $coupon->company_name,
                    'used_count' => $coupon->used_count,
                    'max_uses' => $coupon->max_uses,
                ],
            ]);
        }

        // Coupon is valid
        return response()->json([
            'valid' => true,
            'message' => 'Coupon code is valid',
            'coupon' => [
                'code' => $coupon->code,
                'company' => $coupon->company_name,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
                'event' => [
                    'id' => $coupon->event->id,
                    'name' => $coupon->event->name,
                    'slug' => $coupon->event->slug,
                ],
                'usage' => [
                    'used_count' => $coupon->used_count,
                    'max_uses' => $coupon->max_uses,
                    'remaining_uses' => $remainingUses,
                ],
                'validity' => [
                    'year' => $coupon->year,
                    'valid_from' => $coupon->valid_from?->toIso8601String(),
                    'valid_until' => $coupon->valid_until?->toIso8601String(),
                ],
                'hubspot' => [
                    'company_id' => $coupon->hubspot_company_id,
                    'contact_id' => $coupon->hubspot_contact_id,
                ],
            ],
        ]);
    }

    /**
     * List all coupons for a given event
     *
     * GET /api/events/{eventSlug}/coupons
     *
     * Returns all active coupons for an event. Useful for external sites
     * that need to generate invitations, vouchers, or access codes.
     *
     * Example use case:
     * - Generate personalized email invitations with unique coupon codes
     * - Create printed voucher cards for distribution
     * - Sync codes to external CRM or marketing platform
     *
     * Optionally filter by:
     * - active: true/false (default: true)
     * - year: 2025 (default: current year)
     * - company: "ACME Corp" (fuzzy search)
     */
    public function index(string $eventSlug, Request $request): JsonResponse
    {
        $event = Event::where('slug', $eventSlug)->firstOrFail();

        $query = Coupon::where('event_id', $event->id);

        // Filter by active status (default: true)
        $activeFilter = $request->input('active', 'true');
        if ($activeFilter !== 'all') {
            $query->where('is_active', filter_var($activeFilter, FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by year (default: current year)
        $year = $request->input('year', now()->year);
        if ($year !== 'all') {
            $query->where('year', $year);
        }

        // Filter by company name (fuzzy search)
        if ($request->has('company')) {
            $query->where('company_name', 'like', '%' . $request->input('company') . '%');
        }

        // Pagination
        $perPage = min($request->input('per_page', 100), 500); // Max 500
        $coupons = $query->orderBy('company_name')
            ->orderBy('code')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
            ],
            'coupons' => $coupons->map(function ($coupon) {
                return [
                    'code' => $coupon->code,
                    'company' => $coupon->company_name,
                    'discount_type' => $coupon->discount_type,
                    'discount_value' => $coupon->discount_value,
                    'is_active' => $coupon->is_active,
                    'usage' => [
                        'used_count' => $coupon->used_count,
                        'max_uses' => $coupon->max_uses,
                        'remaining_uses' => $coupon->remaining_uses,
                    ],
                    'year' => $coupon->year,
                    'hubspot_company_id' => $coupon->hubspot_company_id,
                ];
            }),
            'pagination' => [
                'total' => $coupons->total(),
                'per_page' => $coupons->perPage(),
                'current_page' => $coupons->currentPage(),
                'last_page' => $coupons->lastPage(),
                'from' => $coupons->firstItem(),
                'to' => $coupons->lastItem(),
            ],
        ]);
    }
}
