<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Contract extends BaseModel
{
    use HasUuids;

    protected $fillable = [
        'customer_id',
        'worker_id',
        'package_id',
        'start_date',
        'end_date',
        'status',
        'signed_at',
        'reservation_id',
        'total_amount',
        'terms_conditions',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function reservation()
    {
        return $this->belongsTo(WorkerReservation::class, 'reservation_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function isDraft()
    {
        return $this->status === 'Draft';
    }

    public function isAwaitingPayment()
    {
        return $this->status === 'AwaitingPayment';
    }

    public function isActive()
    {
        return $this->status === 'Active';
    }

    public function isCancelled()
    {
        return in_array($this->status, ['Cancelled', 'Terminated']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeAwaitingPayment($query)
    {
        return $query->where('status', 'AwaitingPayment');
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByWorker($query, $workerId)
    {
        return $query->where('worker_id', $workerId);
    }
}