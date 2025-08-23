<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'contract_number',
        'customer_id',
        'worker_id',
        'assigned_employee_id',
        'package_id',
        'delivery_address_id',
        'start_date',
        'end_date',
        'total_amount',
        'original_amount',
        'discount_amount',
        'applied_discount_id',
        'currency',
        'status',
        'payment_status',
        'notes',
        'employee_notes',
        'status_updated_at',
        'status_updated_by',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'status_updated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'delivery_address_id');
    }

    public function appliedDiscount(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'applied_discount_id');
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'assigned_employee_id');
    }

    public function statusUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'status_updated_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'entity_id')
            ->where('entity_name', 'Contract');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByWorker($query, $workerId)
    {
        return $query->where('worker_id', $workerId);
    }

    public function scopeByPackage($query, $packageId)
    {
        return $query->where('package_id', $packageId);
    }

    public function scopeByCurrency($query, $currency)
    {
        return $query->where('currency', $currency);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['Confirmed', 'In Progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'Cancelled');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate]);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function getDurationInMonthsAttribute()
    {
        return $this->start_date->diffInMonths($this->end_date);
    }

    public function getDurationInDaysAttribute()
    {
        return $this->start_date->diffInDays($this->end_date);
    }

    public function getFormattedTotalAmountAttribute()
    {
        $currencySymbols = [
            'SAR' => 'ر.س',
            'USD' => '$',
        ];

        $symbol = $currencySymbols[$this->currency] ?? $this->currency;
        return $symbol . ' ' . number_format($this->total_amount, 2);
    }

    public function getFormattedOriginalAmountAttribute()
    {
        $currencySymbols = [
            'SAR' => 'ر.س',
            'USD' => '$',
        ];

        $symbol = $currencySymbols[$this->currency] ?? $this->currency;
        return $symbol . ' ' . number_format($this->original_amount, 2);
    }

    public function getFormattedDiscountAmountAttribute()
    {
        $currencySymbols = [
            'SAR' => 'ر.س',
            'USD' => '$',
        ];

        $symbol = $currencySymbols[$this->currency] ?? $this->currency;
        return $symbol . ' ' . number_format($this->discount_amount, 2);
    }

    public function getDiscountPercentageAttribute()
    {
        if ($this->original_amount > 0) {
            return round(($this->discount_amount / $this->original_amount) * 100, 2);
        }
        return 0;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'Draft' => 'gray',
            'Pending' => 'yellow',
            'Confirmed' => 'blue',
            'In Progress' => 'green',
            'Completed' => 'green',
            'Cancelled' => 'red',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function getPaymentStatusColorAttribute()
    {
        $colors = [
            'Pending' => 'yellow',
            'Partial' => 'orange',
            'Paid' => 'green',
            'Failed' => 'red',
        ];

        return $colors[$this->payment_status] ?? 'gray';
    }

    public function isActive()
    {
        return in_array($this->status, ['Confirmed', 'In Progress']);
    }

    public function isCompleted()
    {
        return $this->status === 'Completed';
    }

    public function isCancelled()
    {
        return $this->status === 'Cancelled';
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['Draft', 'Pending', 'Confirmed']);
    }

    public function canBeCompleted()
    {
        return $this->status === 'In Progress';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contract) {
            if (!$contract->contract_number) {
                $contract->contract_number = 'C' . str_pad(static::count() + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}