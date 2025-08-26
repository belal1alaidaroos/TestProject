<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'nationality_id',
        'profession_id',
        'contract_duration_id',
        'price',
        'currency',
        'description',
        'is_active',
        'sort_order',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class);
    }

    public function contractDuration(): BelongsTo
    {
        return $this->belongsTo(ContractDuration::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByNationality($query, $nationalityId)
    {
        return $query->where('nationality_id', $nationalityId);
    }

    public function scopeByProfession($query, $professionId)
    {
        return $query->where('profession_id', $professionId);
    }

    public function scopeByDuration($query, $durationId)
    {
        return $query->where('contract_duration_id', $durationId);
    }

    public function scopeByCurrency($query, $currency)
    {
        return $query->where('currency', $currency);
    }

    public function scopeMatching($query, $nationalityId, $professionId, $durationId)
    {
        return $query->where('nationality_id', $nationalityId)
                    ->where('profession_id', $professionId)
                    ->where('contract_duration_id', $durationId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    public function scopeByPriceRange($query, $minPrice = null, $maxPrice = null)
    {
        if ($minPrice) {
            $query->where('price', '>=', $minPrice);
        }
        
        if ($maxPrice) {
            $query->where('price', '<=', $maxPrice);
        }
        
        return $query;
    }

    public function getDisplayNameAttribute()
    {
        return "{$this->nationality->name} - {$this->profession->name} ({$this->contractDuration->name})";
    }

    public function getFormattedPriceAttribute()
    {
        $currencySymbols = [
            'SAR' => 'ر.س',
            'USD' => '$',
        ];

        $symbol = $currencySymbols[$this->currency] ?? $this->currency;
        return $symbol . ' ' . number_format($this->price, 2);
    }

    public function getPriceInCurrency($targetCurrency, $exchangeRate = null)
    {
        if ($this->currency === $targetCurrency) {
            return $this->price;
        }

        if (!$exchangeRate) {
            // Default exchange rates (should come from config or API)
            $rates = [
                'SAR' => ['USD' => 0.27],
                'USD' => ['SAR' => 3.75],
            ];

            $exchangeRate = $rates[$this->currency][$targetCurrency] ?? 1;
        }

        return $this->price * $exchangeRate;
    }
}