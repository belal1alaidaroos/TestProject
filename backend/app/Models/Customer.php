<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends BaseModel
{
    protected $fillable = [
        'app_user_id',
        'company_name_en',
        'company_name_ar',
        'tax_number',
        'commercial_license',
        'contact_person',
        'phone',
        'email',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(WorkerReservation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }
}