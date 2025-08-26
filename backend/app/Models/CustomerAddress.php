<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerAddress extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'customer_id',
        'address_name',
        'city_id',
        'district_id',
        'full_address',
        'latitude',
        'longitude',
        'is_default',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_default' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'entity_id')
            ->where('entity_name', 'CustomerAddress');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'delivery_address_id');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function getFullAddressAttribute($value)
    {
        return $value . ', ' . $this->district->name . ', ' . $this->city->name;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($address) {
            if ($address->is_default) {
                // Remove default from other addresses of the same customer
                static::where('customer_id', $address->customer_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });

        static::updating(function ($address) {
            if ($address->is_default && $address->isDirty('is_default')) {
                // Remove default from other addresses of the same customer
                static::where('customer_id', $address->customer_id)
                    ->where('id', '!=', $address->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }
}