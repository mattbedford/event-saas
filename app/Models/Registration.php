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
        'attendance_status',
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
        'cancelled_at',
        'attended_at',
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
        'cancelled_at' => 'datetime',
        'attended_at' => 'datetime',
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

    /**
     * Mark registration as cancelled
     * Reaccredits coupon use if within cancellation deadline
     */
    public function markAsCancelled(): bool
    {
        // Check if within cancellation deadline
        $deadlineHours = config('coupons.cancellation_deadline_hours', 24);
        $deadline = $this->event->event_date->subHours($deadlineHours);

        if (now()->greaterThan($deadline)) {
            // Past deadline - treat as no-show instead
            return false;
        }

        $this->update([
            'attendance_status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // Reaccredit coupon use if applicable
        if ($this->coupon_code) {
            $coupon = Coupon::where('code', $this->coupon_code)->first();
            if ($coupon) {
                $coupon->decrementUsage();
            }
        }

        return true;
    }

    /**
     * Mark registration as no-show
     * Does NOT reaccredit coupon use
     */
    public function markAsNoShow(): void
    {
        $this->update([
            'attendance_status' => 'no_show',
        ]);

        // Coupon use is NOT reaccredited for no-shows
    }

    /**
     * Mark registration as attended
     */
    public function markAsAttended(): void
    {
        $this->update([
            'attendance_status' => 'attended',
            'attended_at' => now(),
        ]);
    }

    /**
     * Check if cancellation is still allowed
     */
    public function canBeCancelled(): bool
    {
        if ($this->attendance_status !== 'registered') {
            return false; // Already cancelled, no-show, or attended
        }

        $deadlineHours = config('coupons.cancellation_deadline_hours', 24);
        $deadline = $this->event->event_date->subHours($deadlineHours);

        return now()->lessThanOrEqualTo($deadline);
    }

    /**
     * Get cancellation deadline
     */
    public function getCancellationDeadline(): ?\Carbon\Carbon
    {
        $deadlineHours = config('coupons.cancellation_deadline_hours', 24);
        return $this->event->event_date->copy()->subHours($deadlineHours);
    }

    /**
     * Check if registration is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->attendance_status === 'cancelled';
    }

    /**
     * Check if registration was a no-show
     */
    public function isNoShow(): bool
    {
        return $this->attendance_status === 'no_show';
    }

    /**
     * Check if registrant attended
     */
    public function hasAttended(): bool
    {
        return $this->attendance_status === 'attended';
    }

    /**
     * Scope: Get cancelled registrations
     */
    public function scopeCancelled($query)
    {
        return $query->where('attendance_status', 'cancelled');
    }

    /**
     * Scope: Get no-show registrations
     */
    public function scopeNoShow($query)
    {
        return $query->where('attendance_status', 'no_show');
    }

    /**
     * Scope: Get attended registrations
     */
    public function scopeAttended($query)
    {
        return $query->where('attendance_status', 'attended');
    }

    /**
     * Scope: Get active registrations (not cancelled)
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('attendance_status', ['cancelled']);
    }
}
