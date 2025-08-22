<?php

namespace App\Models;

class AuthOtp extends BaseModel
{
    protected $fillable = [
        'phone',
        'otp',
        'expires_at',
        'is_used',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
        'attempts' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
                    ->where('is_used', false)
                    ->where('attempts', '<', 3);
    }

    public function scopeByPhone($query, $phone)
    {
        return $query->where('phone', $phone);
    }

    public function scopeUnused($query)
    {
        return $query->where('is_used', false);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function getIsExpiredAttribute()
    {
        return $this->expires_at < now();
    }

    public function getIsValidAttribute()
    {
        return !$this->is_expired && !$this->is_used && $this->attempts < 3;
    }

    public function incrementAttempts()
    {
        $this->increment('attempts');
    }

    public function markAsUsed()
    {
        $this->update(['is_used' => true]);
    }
}