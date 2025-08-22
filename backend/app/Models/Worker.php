<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Worker extends BaseModel
{
    use HasUuids;

    protected $fillable = [
        'name_en',
        'name_ar',
        'date_of_birth',
        'nationality_id',
        'profession_id',
        'agency_id',
        'recruitment_request_id',
        'status',
        'current_contract_id',
        'photo_file_id',
        'experience_summary',
        'experience_years',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'experience_years' => 'integer',
    ];

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    public function profession()
    {
        return $this->belongsTo(Profession::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function recruitmentRequest()
    {
        return $this->belongsTo(RecruitmentRequest::class);
    }

    public function currentContract()
    {
        return $this->belongsTo(Contract::class, 'current_contract_id');
    }

    public function reservations()
    {
        return $this->hasMany(WorkerReservation::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function activeReservation()
    {
        return $this->hasOne(WorkerReservation::class)
            ->whereIn('state', ['AwaitingContract', 'AwaitingPayment'])
            ->where('expires_at', '>', now());
    }

    public function isReady()
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

    public function getAgeAttribute()
    {
        return $this->date_of_birth->age;
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'Ready');
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
}