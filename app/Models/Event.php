<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'ticket_price',
        'stripe_product_id',
        'hubspot_list_id',
        'event_date',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'settings' => 'array',
        'is_active' => 'boolean',
        'ticket_price' => 'decimal:2',
    ];

    /**
     * Get all registrations for this event
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Get all coupons for this event
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    /**
     * Get active coupons only
     */
    public function activeCoupons(): HasMany
    {
        return $this->coupons()->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>', now());
            })
            ->where(function ($query) {
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            });
    }

    /**
     * Get paid registrations count
     */
    public function paidRegistrationsCount(): int
    {
        return $this->registrations()
            ->where('payment_status', 'paid')
            ->count();
    }

    /**
     * Get total revenue for this event
     */
    public function totalRevenue(): float
    {
        return $this->registrations()
            ->whereIn('payment_status', ['paid', 'partial'])
            ->sum('paid_amount');
    }

    /**
     * Route key for URL generation
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
