<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    protected $fillable = [
        'registration_id',
        'email_template_id',
        'email_chain_id',
        'brevo_message_id',
        'status',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'brevo_stats',
        'error_message',
    ];

    protected $casts = [
        'brevo_stats' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    /**
     * Get the registration this log belongs to
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * Get the email template used
     */
    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    /**
     * Get the email chain (if part of a chain)
     */
    public function emailChain(): BelongsTo
    {
        return $this->belongsTo(EmailChain::class);
    }

    /**
     * Mark email as sent
     */
    public function markAsSent(string $brevoMessageId): void
    {
        $this->update([
            'status' => 'sent',
            'brevo_message_id' => $brevoMessageId,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark email as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark email as opened
     */
    public function markAsOpened(): void
    {
        $this->update([
            'status' => 'opened',
            'opened_at' => now(),
        ]);
    }

    /**
     * Mark email as clicked
     */
    public function markAsClicked(): void
    {
        $this->update([
            'status' => 'clicked',
            'clicked_at' => now(),
        ]);
    }

    /**
     * Mark email as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Update Brevo stats
     */
    public function updateBrevoStats(array $stats): void
    {
        $this->update([
            'brevo_stats' => array_merge($this->brevo_stats ?? [], $stats),
        ]);
    }

    /**
     * Scope for pending emails
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for sent emails
     */
    public function scopeSent($query)
    {
        return $query->whereIn('status', ['sent', 'delivered', 'opened', 'clicked']);
    }

    /**
     * Scope for failed emails
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['bounced', 'failed']);
    }
}
