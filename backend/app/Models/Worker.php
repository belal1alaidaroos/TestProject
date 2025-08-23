<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Worker extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'worker_number',
        'name_en',
        'name_ar',
        'date_of_birth',
        'nationality_id',
        'profession_id',
        'agency_id',
        'recruitment_request_id',
        'iqama_number',
        'iqama_expiry_date',
        'bank_account_number',
        'sim_card_number',
        'lifecycle_status',
        'arrival_date',
        'notes',
        'status',
        'current_contract_id',
        'photo_file_id',
        'experience_summary',
        'experience_years',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'iqama_expiry_date' => 'date',
        'arrival_date' => 'date',
        'experience_years' => 'integer',
    ];

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function recruitmentRequest(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequest::class);
    }

    public function currentContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'current_contract_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(WorkerReservation::class);
    }

    public function problems(): HasMany
    {
        return $this->hasMany(WorkerProblem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'entity_id')
            ->where('entity_name', 'Worker');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByNationality($query, $nationalityId)
    {
        return $query->where('nationality_id', $nationalityId);
    }

    public function scopeByProfession($query, $professionId)
    {
        return $query->where('profession_id', $professionId);
    }

    public function scopeByAgency($query, $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeByLifecycleStatus($query, $lifecycleStatus)
    {
        return $query->where('lifecycle_status', $lifecycleStatus);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'Ready');
    }

    public function scopeAssigned($query)
    {
        return $query->whereIn('status', ['AssignedToContract', 'In Progress']);
    }

    public function scopeByExperience($query, $minYears = null, $maxYears = null)
    {
        if ($minYears) {
            $query->where('experience_years', '>=', $minYears);
        }
        
        if ($maxYears) {
            $query->where('experience_years', '<=', $maxYears);
        }
        
        return $query;
    }

    public function scopeByAge($query, $minAge = null, $maxAge = null)
    {
        if ($minAge) {
            $query->where('date_of_birth', '<=', now()->subYears($minAge));
        }
        
        if ($maxAge) {
            $query->where('date_of_birth', '>=', now()->subYears($maxAge));
        }
        
        return $query;
    }

    public function scopeByArrivalDate($query, $startDate = null, $endDate = null)
    {
        if ($startDate) {
            $query->where('arrival_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('arrival_date', '<=', $endDate);
        }
        
        return $query;
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function getNameAttribute()
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth->age;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'Ready' => 'green',
            'ReservedAwaitingContract' => 'yellow',
            'ReservedAwaitingPayment' => 'orange',
            'AssignedToContract' => 'blue',
            'Medical Check' => 'purple',
            'Iqama Issued' => 'indigo',
            'Bank Account' => 'cyan',
            'SIM Card Issued' => 'teal',
            'Ready to Work' => 'green',
            'On Leave' => 'gray',
            'Terminated' => 'red',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function getLifecycleStatusColorAttribute()
    {
        $colors = [
            'Medical Check' => 'purple',
            'Iqama Issued' => 'indigo',
            'Bank Account' => 'cyan',
            'SIM Card Issued' => 'teal',
            'Ready to Work' => 'green',
        ];

        return $colors[$this->lifecycle_status] ?? 'gray';
    }

    public function isAvailable()
    {
        return $this->status === 'Ready';
    }

    public function isReserved()
    {
        return in_array($this->status, ['ReservedAwaitingContract', 'ReservedAwaitingPayment']);
    }

    public function isAssigned()
    {
        return $this->status === 'AssignedToContract';
    }

    public function isActive()
    {
        return in_array($this->status, ['Ready', 'AssignedToContract', 'In Progress']);
    }

    public function isTerminated()
    {
        return $this->status === 'Terminated';
    }

    public function canTransitionTo($newStatus)
    {
        $allowedTransitions = [
            'Ready' => ['ReservedAwaitingContract', 'Medical Check'],
            'ReservedAwaitingContract' => ['Ready', 'ReservedAwaitingPayment'],
            'ReservedAwaitingPayment' => ['Ready', 'AssignedToContract'],
            'AssignedToContract' => ['Ready', 'In Progress'],
            'Medical Check' => ['Iqama Issued', 'Ready'],
            'Iqama Issued' => ['Bank Account'],
            'Bank Account' => ['SIM Card Issued'],
            'SIM Card Issued' => ['Ready to Work'],
            'Ready to Work' => ['Ready', 'On Leave', 'Terminated'],
            'On Leave' => ['Ready to Work', 'Terminated'],
        ];

        return in_array($newStatus, $allowedTransitions[$this->status] ?? []);
    }

    public function transitionTo($newStatus)
    {
        if ($this->canTransitionTo($newStatus)) {
            $this->update(['status' => $newStatus]);
            return true;
        }
        return false;
    }

    public function updateLifecycleStatus($newLifecycleStatus)
    {
        $allowedTransitions = [
            'Medical Check' => ['Iqama Issued'],
            'Iqama Issued' => ['Bank Account'],
            'Bank Account' => ['SIM Card Issued'],
            'SIM Card Issued' => ['Ready to Work'],
        ];

        if (in_array($newLifecycleStatus, $allowedTransitions[$this->lifecycle_status] ?? [])) {
            $this->update(['lifecycle_status' => $newLifecycleStatus]);
            return true;
        }
        return false;
    }

    public function hasValidIqama()
    {
        return $this->iqama_number && $this->iqama_expiry_date && $this->iqama_expiry_date > now();
    }

    public function hasBankAccount()
    {
        return !empty($this->bank_account_number);
    }

    public function hasSimCard()
    {
        return !empty($this->sim_card_number);
    }

    public function isReadyToWork()
    {
        return $this->lifecycle_status === 'Ready to Work';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($worker) {
            if (!$worker->worker_number) {
                $worker->worker_number = 'W' . str_pad(static::count() + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}