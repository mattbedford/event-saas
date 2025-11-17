<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'coupon_id',
        'registration_id',
        'event_id',
        'status',
        'expires_at',
        'confirmed_at',
        'released_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    /**
     * Get the coupon this reservation is for
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the registration this reservation is for
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * Get the event this reservation is for
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Check if reservation is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return now()->greaterThan($this->expires_at) && $this->status === 'reserved';
    }

    /**
     * Confirm the reservation (user completed payment/registration)
     */
    public function confirm(): void
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Release the reservation (user abandoned or changed coupon)
     */
    public function release(): void
    {
        $this->update([
            'status' => 'released',
            'released_at' => now(),
        ]);
    }

    /**
     * Mark reservation as expired (auto-cleanup)
     */
    public function expire(): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }

    /**
     * Scope: Get active reservations (reserved, not expired)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'reserved')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope: Get expired reservations
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'reserved')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope: Get confirmed reservations
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }
}
