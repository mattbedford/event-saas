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
        'max_seats',
        'stripe_product_id',
        'hubspot_list_id',
        'event_date',
        'settings',
        'is_active',
        'charity_enabled',
        'charity_name',
        'charity_description',
        'charity_logo_url',
        'charity_website_url',
        'charity_donation_url',
        'charity_suggested_amount',
        'automatic_invoices_enabled',
        'invoice_message',
        'invoice_contact_email',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'settings' => 'array',
        'is_active' => 'boolean',
        'ticket_price' => 'decimal:2',
        'charity_enabled' => 'boolean',
        'charity_suggested_amount' => 'decimal:2',
        'automatic_invoices_enabled' => 'boolean',
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
     * Get all ticket types for this event
     */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class)->orderBy('sort_order');
    }

    /**
     * Get all waitlist entries for this event
     */
    public function waitlists(): HasMany
    {
        return $this->hasMany(Waitlist::class);
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
     * Get active registrations count (for capacity calculation)
     * Counts ALL registrations (paid + pending) that occupy a seat
     * This includes free voucher registrations which are marked as 'paid'
     */
    public function activeRegistrationsCount(): int
    {
        return $this->registrations()
            ->whereIn('payment_status', ['paid', 'pending'])
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
     * Get remaining available seats
     * Returns null if max_seats is not set (unlimited)
     */
    public function remainingSeats(): ?int
    {
        if ($this->max_seats === null) {
            return null;
        }

        return max(0, $this->max_seats - $this->activeRegistrationsCount());
    }

    /**
     * Check if the event has available seats
     * Returns true if unlimited or seats available
     */
    public function hasAvailableSeats(): bool
    {
        if ($this->max_seats === null) {
            return true; // Unlimited capacity
        }

        return $this->remainingSeats() > 0;
    }

    /**
     * Check if event is nearly full (90% capacity)
     */
    public function isNearlyFull(): bool
    {
        if ($this->max_seats === null) {
            return false;
        }

        $occupancyRate = ($this->activeRegistrationsCount() / $this->max_seats) * 100;
        return $occupancyRate >= 90;
    }

    /**
     * Get capacity percentage filled
     */
    public function capacityPercentage(): ?float
    {
        if ($this->max_seats === null || $this->max_seats === 0) {
            return null;
        }

        return round(($this->activeRegistrationsCount() / $this->max_seats) * 100, 1);
    }

    /**
     * Route key for URL generation
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
