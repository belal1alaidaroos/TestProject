<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'phone',
        'session_token',
        'expires_at',
        'status',
        'payment_method',
        'otp_hash',
        'otp_attempts',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'otp_attempts' => 'integer',
    ];

    /**
     * Get the contract that owns the payment session
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Check if the session is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    /**
     * Check if the session is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the session is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the session is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get remaining time in seconds
     */
    public function getRemainingTimeAttribute(): int
    {
        return max(0, now()->diffInSeconds($this->expires_at));
    }

    /**
     * Scope for pending sessions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for active (non-expired) sessions
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope for expired sessions
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}