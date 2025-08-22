<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends BaseModel
{
    protected $fillable = [
        'app_user_id',
        'company_name_en',
        'company_name_ar',
        'tax_number',
        'commercial_license',
        'contact_person',
        'contact_phone',
        'contact_email',
        'specialization',
        'experience_years',
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

    public function proposals(): HasMany
    {
        return $this->hasMany(SupplierProposal::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeBySpecialization($query, $specialization)
    {
        return $query->where('specialization', 'like', "%{$specialization}%");
    }
}