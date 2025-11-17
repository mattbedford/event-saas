<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailChain extends Model
{
    protected $fillable = [
        'event_id',
        'email_template_id',
        'send_after_minutes',
        'order',
        'is_active',
        'send_only_before_event',
    ];

    protected $casts = [
        'send_after_minutes' => 'integer',
        'order' => 'integer',
        'is_active' => 'boolean',
        'send_only_before_event' => 'boolean',
    ];

    /**
     * Get the event this chain belongs to
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the email template for this chain
     */
    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    /**
     * Get all email logs for this chain
     */
    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    /**
     * Calculate when this email should be sent for a given registration
     */
    public function calculateSendTime(\Carbon\Carbon $registrationDate): \Carbon\Carbon
    {
        return $registrationDate->copy()->addMinutes($this->send_after_minutes);
    }

    /**
     * Check if this email should be sent (not after event date if configured)
     */
    public function shouldSendForRegistration(Registration $registration): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $sendTime = $this->calculateSendTime($registration->created_at);

        // Check if we shouldn't send after the event
        if ($this->send_only_before_event) {
            $eventDate = $registration->event->event_date;
            if ($sendTime->isAfter($eventDate)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Scope to get active chains
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by chain order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
