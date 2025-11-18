<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketType extends Model
{
    use HasFactory;
    protected $fillable = [
        'event_id',
        'name',
        'description',
        'price',
        'quantity_available',
        'quantity_sold',
        'sale_starts_at',
        'sale_ends_at',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        $now = now();
        return $query->active()
            ->where(function ($q) use ($now) {
                $q->whereNull('sale_starts_at')
                    ->orWhere('sale_starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('sale_ends_at')
                    ->orWhere('sale_ends_at', '>', $now);
            });
    }

    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->sale_starts_at && $this->sale_starts_at->isFuture()) {
            return false;
        }

        if ($this->sale_ends_at && $this->sale_ends_at->isPast()) {
            return false;
        }

        if ($this->quantity_available !== null && $this->quantity_sold >= $this->quantity_available) {
            return false;
        }

        return true;
    }

    public function hasTicketsRemaining(): bool
    {
        if ($this->quantity_available === null) {
            return true; // Unlimited
        }

        return $this->quantity_sold < $this->quantity_available;
    }

    public function remainingTickets(): ?int
    {
        if ($this->quantity_available === null) {
            return null; // Unlimited
        }

        return max(0, $this->quantity_available - $this->quantity_sold);
    }

    public function incrementSold(): void
    {
        $this->increment('quantity_sold');
    }

    public function decrementSold(): void
    {
        $this->decrement('quantity_sold');
    }

    public function getStatusBadge(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        $now = now();

        if ($this->sale_starts_at && $this->sale_starts_at->isFuture()) {
            return 'upcoming';
        }

        if ($this->sale_ends_at && $this->sale_ends_at->isPast()) {
            return 'ended';
        }

        if (!$this->hasTicketsRemaining()) {
            return 'sold_out';
        }

        return 'active';
    }
}
