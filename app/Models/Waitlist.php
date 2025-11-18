<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Waitlist extends Model
{
    use HasFactory;
    protected $fillable = [
        'event_id',
        'email',
        'full_name',
        'phone',
        'company',
        'status',
        'notified_at',
        'expires_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    public function scopeNotified($query)
    {
        return $query->where('status', 'notified');
    }

    public function scopeForEvent($query, int $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function markAsNotified(): void
    {
        $this->update([
            'status' => 'notified',
            'notified_at' => now(),
            'expires_at' => now()->addHours(24), // 24-hour window to register
        ]);
    }

    public function markAsRegistered(): void
    {
        $this->update(['status' => 'registered']);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    public function isExpired(): bool
    {
        return $this->status === 'notified'
            && $this->expires_at
            && $this->expires_at->isPast();
    }

    public function getPosition(): int
    {
        return static::where('event_id', $this->event_id)
            ->where('status', 'waiting')
            ->where('created_at', '<=', $this->created_at)
            ->where('id', '<=', $this->id)
            ->count();
    }
}
