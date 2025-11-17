<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'email',
        'name',
        'surname',
        'company',
        'phone',
        'additional_fields',
        'payment_status',
        'paid_amount',
        'expected_amount',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'hubspot_id',
        'coupon_code',
        'discount_amount',
        'badge_generated',
        'badge_generated_at',
        'badge_file_path',
        'confirmation_sent',
        'confirmation_sent_at',
        'reminder_sent',
        'reminder_sent_at',
    ];

    protected $casts = [
        'additional_fields' => 'array',
        'paid_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'badge_generated' => 'boolean',
        'badge_generated_at' => 'datetime',
        'confirmation_sent' => 'boolean',
        'confirmation_sent_at' => 'datetime',
        'reminder_sent' => 'boolean',
        'reminder_sent_at' => 'datetime',
    ];

    /**
     * Get the event this registration belongs to
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the full name of the registrant
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->name} {$this->surname}";
    }

    /**
     * Check if payment is complete
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if payment is partial
     */
    public function isPartiallyPaid(): bool
    {
        return $this->payment_status === 'partial';
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    /**
     * Get remaining amount to be paid
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->expected_amount - $this->paid_amount);
    }

    /**
     * Mark as paid (critical - this prevents the partial payment bug!)
     */
    public function markAsPaid(float $amount): void
    {
        $this->paid_amount = $amount;

        // IMPORTANT: Always update payment_status based on amount paid
        if ($amount >= $this->expected_amount) {
            $this->payment_status = 'paid';
        } elseif ($amount > 0) {
            $this->payment_status = 'partial';
        }

        $this->save();
    }

    /**
     * Mark badge as generated
     */
    public function markBadgeAsGenerated(string $filePath): void
    {
        $this->update([
            'badge_generated' => true,
            'badge_generated_at' => now(),
            'badge_file_path' => $filePath,
        ]);
    }

    /**
     * Mark confirmation email as sent
     */
    public function markConfirmationSent(): void
    {
        $this->update([
            'confirmation_sent' => true,
            'confirmation_sent_at' => now(),
        ]);
    }

    /**
     * Mark reminder email as sent
     */
    public function markReminderSent(): void
    {
        $this->update([
            'reminder_sent' => true,
            'reminder_sent_at' => now(),
        ]);
    }

    /**
     * Scope: Get only paid registrations
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope: Get only pending registrations
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    /**
     * Scope: Get registrations that need badge generation
     */
    public function scopeNeedsBadge($query)
    {
        return $query->where('payment_status', 'paid')
            ->where('badge_generated', false);
    }

    /**
     * Scope: Get registrations that need confirmation email
     */
    public function scopeNeedsConfirmation($query)
    {
        return $query->where('payment_status', 'paid')
            ->where('confirmation_sent', false);
    }
}
