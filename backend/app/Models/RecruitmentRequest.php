<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentRequest extends BaseModel
{
    protected $fillable = [
        'title_en',
        'title_ar',
        'description_en',
        'description_ar',
        'profession_id',
        'quantity_required',
        'quantity_awarded',
        'unit_price_range_min',
        'unit_price_range_max',
        'currency',
        'valid_until',
        'status',
        'priority',
        'requirements',
    ];

    protected $casts = [
        'quantity_required' => 'integer',
        'quantity_awarded' => 'integer',
        'unit_price_range_min' => 'decimal:2',
        'unit_price_range_max' => 'decimal:2',
        'valid_until' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(SupplierProposal::class, 'request_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'Open');
    }

    public function scopePartiallyAwarded($query)
    {
        return $query->where('status', 'PartiallyAwarded');
    }

    public function scopeFullyAwarded($query)
    {
        return $query->where('status', 'FullyAwarded');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'Closed');
    }

    public function scopeValid($query)
    {
        return $query->where('valid_until', '>', now());
    }

    public function scopeByProfession($query, $professionId)
    {
        return $query->where('profession_id', $professionId);
    }

    public function getRemainingQuantityAttribute()
    {
        return $this->quantity_required - $this->quantity_awarded;
    }

    public function getIsFullyAwardedAttribute()
    {
        return $this->quantity_awarded >= $this->quantity_required;
    }

    public function getIsExpiredAttribute()
    {
        return $this->valid_until < now();
    }
}