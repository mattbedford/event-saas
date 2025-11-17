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
        'hubspot_company_id',
        'hubspot_contact_id',
        'company_name',
        'discount_type',
        'discount_value',
        'max_uses',
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
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'year' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'is_manual' => 'boolean',
        'restrictions' => 'array',
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
}
