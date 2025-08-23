<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractDuration extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name_en',
        'name_ar',
        'months',
        'description',
        'is_active',
        'sort_order',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'months' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('months');
    }

    public function scopeByMonths($query, $minMonths = null, $maxMonths = null)
    {
        if ($minMonths) {
            $query->where('months', '>=', $minMonths);
        }
        
        if ($maxMonths) {
            $query->where('months', '<=', $maxMonths);
        }
        
        return $query;
    }

    public function getNameAttribute()
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function getDisplayNameAttribute()
    {
        return "{$this->name} ({$this->months} " . trans_choice('months', $this->months) . ")";
    }
}