<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerReservation extends BaseModel
{
    protected $fillable = [
        'worker_id',
        'customer_id',
        'reservation_date',
        'expires_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'reservation_date' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'Expired');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'Cancelled');
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByWorker($query, $workerId)
    {
        return $query->where('worker_id', $workerId);
    }

    public function scopeExpiredReservations($query)
    {
        return $query->where('status', 'Active')
                    ->where('expires_at', '<', now());
    }

    public function getIsExpiredAttribute()
    {
        return $this->expires_at < now();
    }

    public function getTimeRemainingAttribute()
    {
        if ($this->expires_at < now()) {
            return 0;
        }
        
        return $this->expires_at->diffInSeconds(now());
    }
}