<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
        'percentage',
        'conditions',
        'valid_from',
        'valid_until',
        'is_active',
        'max_uses',
        'used_count',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'conditions' => 'array',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'max_uses' => 'integer',
        'used_count' => 'integer',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'applied_discount_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('valid_from', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('valid_until')
                          ->orWhere('valid_until', '>=', now());
                    });
    }

    public function scopeAvailable($query)
    {
        return $query->active()
                    ->where(function ($q) {
                        $q->whereNull('max_uses')
                          ->orWhereRaw('used_count < max_uses');
                    });
    }

    public function scopeByPercentage($query, $minPercentage = null, $maxPercentage = null)
    {
        if ($minPercentage) {
            $query->where('percentage', '>=', $minPercentage);
        }
        
        if ($maxPercentage) {
            $query->where('percentage', '<=', $maxPercentage);
        }
        
        return $query;
    }

    public function isAvailable()
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->valid_from > now()) {
            return false;
        }

        if ($this->valid_until && $this->valid_until < now()) {
            return false;
        }

        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function canApplyToContract($contract)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        if (!$this->conditions) {
            return true;
        }

        // Check conditions
        foreach ($this->conditions as $condition) {
            switch ($condition['type']) {
                case 'min_amount':
                    if ($contract->original_amount < $condition['value']) {
                        return false;
                    }
                    break;
                case 'max_amount':
                    if ($contract->original_amount > $condition['value']) {
                        return false;
                    }
                    break;
                case 'nationality':
                    if ($contract->worker->nationality_id !== $condition['value']) {
                        return false;
                    }
                    break;
                case 'profession':
                    if ($contract->worker->profession_id !== $condition['value']) {
                        return false;
                    }
                    break;
                case 'duration':
                    if ($contract->package->contract_duration_id !== $condition['value']) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    public function calculateDiscountAmount($originalAmount)
    {
        return ($originalAmount * $this->percentage) / 100;
    }

    public function incrementUsage()
    {
        $this->increment('used_count');
    }
}