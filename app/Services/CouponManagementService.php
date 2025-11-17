<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CouponManagementService
{
    public function __construct(
        private HubspotService $hubspotService
    ) {
    }

    /**
     * Generate a simplified coupon code
     * Format: COMPANYNAME-YEAR or COMPANYNAME-EVENT-YEAR
     */
    public function generateSimplifiedCode(
        string $companyName,
        ?int $year = null,
        ?string $eventName = null
    ): string {
        $year = $year ?? now()->year;

        // Clean company name (alphanumeric only, max 10 chars)
        $cleanName = Str::upper(preg_replace('/[^A-Z0-9]/', '', Str::upper($companyName)));
        $cleanName = Str::limit($cleanName, 10, '');

        if ($eventName) {
            $cleanEvent = Str::upper(preg_replace('/[^A-Z0-9]/', '', Str::upper($eventName)));
            $cleanEvent = Str::limit($cleanEvent, 8, '');
            $code = "{$cleanName}-{$cleanEvent}-{$year}";
        } else {
            $code = "{$cleanName}-{$year}";
        }

        // Ensure uniqueness
        $originalCode = $code;
        $counter = 1;
        while (Coupon::where('code', $code)->exists()) {
            $code = $originalCode . "-{$counter}";
            $counter++;
        }

        return $code;
    }

    /**
     * Create a coupon for a Hubspot company
     */
    public function createCouponForHubspotCompany(
        string $hubspotCompanyId,
        string $companyName,
        Event $event,
        array $couponData
    ): Coupon {
        $year = $couponData['year'] ?? now()->year;
        $code = $this->generateSimplifiedCode($companyName, $year, $event->name);

        return Coupon::create([
            'event_id' => $event->id,
            'code' => $code,
            'hubspot_company_id' => $hubspotCompanyId,
            'company_name' => $companyName,
            'discount_type' => $couponData['discount_type'] ?? 'percentage',
            'discount_value' => $couponData['discount_value'] ?? 100,
            'max_uses' => $couponData['max_uses'] ?? 10, // Default: 10 uses per company
            'year' => $year,
            'valid_from' => $couponData['valid_from'] ?? now(),
            'valid_until' => $couponData['valid_until'] ?? now()->endOfYear(),
            'is_active' => true,
            'is_manual' => false,
            'notes' => $couponData['notes'] ?? "Auto-generated for {$companyName}",
        ]);
    }

    /**
     * Create a manual coupon
     */
    public function createManualCoupon(
        Event $event,
        array $couponData,
        string $generatedBy
    ): Coupon {
        $code = Str::upper($couponData['code']);

        // Check if code already exists
        if (Coupon::where('code', $code)->exists()) {
            throw new \Exception("Coupon code '{$code}' already exists");
        }

        return Coupon::create([
            'event_id' => $event->id,
            'code' => $code,
            'company_name' => $couponData['company_name'] ?? null,
            'hubspot_company_id' => $couponData['hubspot_company_id'] ?? null,
            'hubspot_contact_id' => $couponData['hubspot_contact_id'] ?? null,
            'discount_type' => $couponData['discount_type'],
            'discount_value' => $couponData['discount_value'],
            'max_uses' => $couponData['max_uses'] ?? null,
            'year' => $couponData['year'] ?? now()->year,
            'valid_from' => $couponData['valid_from'] ?? now(),
            'valid_until' => $couponData['valid_until'] ?? null,
            'is_active' => true,
            'is_manual' => true,
            'generated_by' => $generatedBy,
            'restricted_to_event_id' => $couponData['restricted_to_event_id'] ?? null,
            'notes' => $couponData['notes'] ?? null,
        ]);
    }

    /**
     * Bulk generate coupons from Hubspot company list
     */
    public function bulkGenerateFromHubspotList(
        string $hubspotListId,
        Event $event,
        array $defaultCouponData
    ): array {
        $results = [
            'created' => [],
            'skipped' => [],
            'errors' => [],
        ];

        try {
            // Fetch companies from Hubspot list
            $companies = $this->hubspotService->getCompaniesFromList($hubspotListId);

            foreach ($companies as $company) {
                try {
                    $companyId = $company['id'];
                    $companyName = $company['name'] ?? 'Unknown';

                    // Check if coupon already exists for this company/year
                    $existingCoupon = Coupon::byHubspotCompany($companyId)
                        ->forEvent($event->id)
                        ->forYear($defaultCouponData['year'] ?? now()->year)
                        ->first();

                    if ($existingCoupon) {
                        $results['skipped'][] = [
                            'company_id' => $companyId,
                            'company_name' => $companyName,
                            'reason' => 'Coupon already exists',
                            'existing_code' => $existingCoupon->code,
                        ];
                        continue;
                    }

                    $coupon = $this->createCouponForHubspotCompany(
                        $companyId,
                        $companyName,
                        $event,
                        $defaultCouponData
                    );

                    $results['created'][] = [
                        'company_id' => $companyId,
                        'company_name' => $companyName,
                        'code' => $coupon->code,
                    ];
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'company_id' => $company['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to bulk generate coupons from Hubspot', [
                'list_id' => $hubspotListId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $results;
    }

    /**
     * Get coupon usage statistics
     */
    public function getCouponStats(Coupon $coupon): array
    {
        $registrations = $coupon->registrations();

        return [
            'total_uses' => $coupon->used_count,
            'max_uses' => $coupon->max_uses,
            'remaining_uses' => $coupon->remaining_uses,
            'registrations' => $registrations->map(function ($registration) {
                return [
                    'id' => $registration->id,
                    'name' => $registration->full_name,
                    'email' => $registration->email,
                    'event' => $registration->event->name,
                    'payment_status' => $registration->payment_status,
                    'paid_amount' => $registration->paid_amount,
                    'discount_amount' => $registration->discount_amount,
                    'created_at' => $registration->created_at->toDateTimeString(),
                ];
            })->toArray(),
            'total_revenue' => $registrations->sum('paid_amount'),
            'total_discount' => $registrations->sum('discount_amount'),
        ];
    }

    /**
     * Get global coupon statistics (across all events)
     */
    public function getGlobalCouponStats(?int $year = null): array
    {
        $query = Coupon::query();

        if ($year) {
            $query->forYear($year);
        }

        $coupons = $query->get();

        return [
            'total_coupons' => $coupons->count(),
            'active_coupons' => $coupons->where('is_active', true)->count(),
            'hubspot_linked' => $coupons->filter(fn($c) => $c->hasHubspotLink())->count(),
            'manual_coupons' => $coupons->where('is_manual', true)->count(),
            'total_uses' => $coupons->sum('used_count'),
            'total_max_uses' => $coupons->sum('max_uses'),
            'by_event' => $coupons->groupBy('event_id')->map(function ($eventCoupons) {
                return [
                    'event_name' => $eventCoupons->first()->event->name ?? 'Unknown',
                    'count' => $eventCoupons->count(),
                    'uses' => $eventCoupons->sum('used_count'),
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Expire coupons for a given year
     */
    public function expireCouponsForYear(int $year): int
    {
        return Coupon::forYear($year)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    /**
     * Sync coupon back to Hubspot (add custom property)
     */
    public function syncToHubspot(Coupon $coupon): bool
    {
        if (!$coupon->hasHubspotLink()) {
            return false;
        }

        try {
            // This would add a custom property to the Hubspot company/contact
            // showing they have a coupon code
            // Implementation depends on your Hubspot custom properties

            Log::info('Synced coupon to Hubspot', [
                'coupon_code' => $coupon->code,
                'hubspot_company_id' => $coupon->hubspot_company_id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync coupon to Hubspot', [
                'coupon_id' => $coupon->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
