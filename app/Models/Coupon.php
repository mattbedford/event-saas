<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'code',
        'coupon_type',
        'scope',
        'hubspot_company_id',
        'hubspot_contact_id',
        'company_name',
        'discount_type',
        'discount_value',
        'max_uses',
        'max_uses_global',
        'max_uses_per_event',
        'used_count',
        'valid_from',
        'valid_until',
        'year',
        'is_active',
        'is_manual',
        'generated_by',
        'restricted_to_event_id',
        'notes',
        'restrictions',
        'metadata',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_uses' => 'integer',
        'max_uses_global' => 'integer',
        'max_uses_per_event' => 'integer',
        'used_count' => 'integer',
        'year' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'is_manual' => 'boolean',
        'restrictions' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the event this coupon belongs to
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the event this coupon is restricted to (if any)
     */
    public function restrictedToEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'restricted_to_event_id');
    }

    /**
     * Get all registrations that used this coupon
     */
    public function registrations()
    {
        return Registration::where('coupon_code', $this->code)->get();
    }

    /**
     * Check if coupon is expired based on year
     */
    public function isExpiredByYear(): bool
    {
        if (!$this->year) {
            return false; // No year restriction
        }

        return now()->year > $this->year;
    }

    /**
     * Check if coupon is linked to Hubspot
     */
    public function hasHubspotLink(): bool
    {
        return !empty($this->hubspot_company_id) || !empty($this->hubspot_contact_id);
    }

    /**
     * Get display name (company name or code)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?? $this->code;
    }

    /**
     * Check if coupon is currently valid
     */
    public function isValid(): bool
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check date validity
        $now = now();

        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        // Check usage limit
        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount amount for a given price
     */
    public function calculateDiscount(float $price): float
    {
        if ($this->discount_type === 'percentage') {
            return round($price * ($this->discount_value / 100), 2);
        }

        // Fixed discount
        return min($this->discount_value, $price); // Can't discount more than the price
    }

    /**
     * Apply discount to a price
     */
    public function applyDiscount(float $price): float
    {
        $discount = $this->calculateDiscount($price);
        return max(0, $price - $discount);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    /**
     * Decrement usage count (for refunds)
     */
    public function decrementUsage(): void
    {
        if ($this->used_count > 0) {
            $this->decrement('used_count');
        }
    }

    /**
     * Check if coupon has uses remaining
     */
    public function hasUsesRemaining(): bool
    {
        if (!$this->max_uses) {
            return true; // Unlimited uses
        }

        return $this->used_count < $this->max_uses;
    }

    /**
     * Get remaining uses
     */
    public function getRemainingUsesAttribute(): ?int
    {
        if (!$this->max_uses) {
            return null; // Unlimited
        }

        return max(0, $this->max_uses - $this->used_count);
    }

    /**
     * Scope: Get only active coupons
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Get only valid coupons (active + date range + usage)
     */
    public function scopeValid($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>', $now);
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')
                    ->orWhereRaw('used_count < max_uses');
            });
    }

    /**
     * Scope: Find by code for a specific event
     */
    public function scopeForEvent($query, int $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    /**
     * Scope: Find by code (case-insensitive)
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', strtoupper($code));
    }

    /**
     * Scope: Get coupons for a specific year
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope: Get coupons linked to Hubspot
     */
    public function scopeLinkedToHubspot($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('hubspot_company_id')
                ->orWhereNotNull('hubspot_contact_id');
        });
    }

    /**
     * Scope: Get manual coupons
     */
    public function scopeManual($query)
    {
        return $query->where('is_manual', true);
    }

    /**
     * Scope: Get auto-generated coupons
     */
    public function scopeAutoGenerated($query)
    {
        return $query->where('is_manual', false);
    }

    /**
     * Scope: Get coupons by Hubspot company ID
     */
    public function scopeByHubspotCompany($query, string $companyId)
    {
        return $query->where('hubspot_company_id', $companyId);
    }

    /**
     * Get actual global uses (across all events in the year)
     * Excludes cancelled registrations
     */
    public function getActualUsesGlobal(): int
    {
        return Registration::where('coupon_code', $this->code)
            ->whereHas('event', function ($query) {
                $query->whereYear('event_date', $this->year ?? now()->year);
            })
            ->whereNotIn('attendance_status', ['cancelled'])
            ->count();
    }

    /**
     * Get actual uses for a specific event
     * Excludes cancelled registrations
     */
    public function getActualUsesForEvent(int $eventId): int
    {
        return Registration::where('coupon_code', $this->code)
            ->where('event_id', $eventId)
            ->whereNotIn('attendance_status', ['cancelled'])
            ->count();
    }

    /**
     * Get remaining global uses
     * Returns null if unlimited
     */
    public function getRemainingUsesGlobal(): ?int
    {
        if ($this->max_uses_global === null) {
            return null; // Unlimited globally
        }

        return max(0, $this->max_uses_global - $this->getActualUsesGlobal());
    }

    /**
     * Get remaining uses for a specific event
     * Returns null if unlimited
     */
    public function getRemainingUsesForEvent(int $eventId): ?int
    {
        if ($this->max_uses_per_event === null) {
            return null; // Unlimited per event
        }

        return max(0, $this->max_uses_per_event - $this->getActualUsesForEvent($eventId));
    }

    /**
     * Check if coupon can be used for a specific event
     * Validates both global and per-event limits
     */
    public function canBeUsedForEvent(int $eventId): bool
    {
        // Check basic validity
        if (!$this->isValid()) {
            return false;
        }

        // Check year expiration
        if ($this->isExpiredByYear()) {
            return false;
        }

        // For event-scoped coupons, verify it's the right event
        if ($this->scope === 'event' && $this->event_id !== null && $this->event_id !== $eventId) {
            return false;
        }

        // Check global limit
        if ($this->max_uses_global !== null) {
            $globalRemaining = $this->getRemainingUsesGlobal();
            if ($globalRemaining !== null && $globalRemaining <= 0) {
                return false;
            }
        }

        // Check per-event limit
        if ($this->max_uses_per_event !== null) {
            $eventRemaining = $this->getRemainingUsesForEvent($eventId);
            if ($eventRemaining !== null && $eventRemaining <= 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get coupon type configuration
     */
    public function getTypeConfig(): ?array
    {
        return config("coupons.types.{$this->coupon_type}");
    }

    /**
     * Get coupon type label
     */
    public function getTypeLabel(): string
    {
        $config = $this->getTypeConfig();
        return $config['label'] ?? ucfirst(str_replace('_', ' ', $this->coupon_type));
    }

    /**
     * Apply default limits based on coupon type
     */
    public function applyTypeDefaults(): void
    {
        $config = $this->getTypeConfig();

        if ($config) {
            $this->max_uses_per_event = $this->max_uses_per_event ?? $config['max_uses_per_event'];
            $this->max_uses_global = $this->max_uses_global ?? $config['max_uses_global'];
        }
    }

    /**
     * Scope: Get coupons by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('coupon_type', $type);
    }

    /**
     * Scope: Get global coupons
     */
    public function scopeGlobal($query)
    {
        return $query->where('scope', 'global');
    }

    /**
     * Scope: Get event-specific coupons
     */
    public function scopeEventSpecific($query)
    {
        return $query->where('scope', 'event');
    }
}
