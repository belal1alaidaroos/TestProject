<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class AppUser extends Authenticatable
{
    use HasUuids, HasApiTokens, Notifiable, HasRoles;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'user_type',
        'agency_id',
        'is_active',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function roles()
    {
        return $this->belongsToMany(AppRole::class, 'app_user_roles', 'app_user_id', 'app_role_id');
    }

    public function reservations()
    {
        return $this->hasMany(WorkerReservation::class, 'customer_id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'customer_id');
    }

    public function proposals()
    {
        return $this->hasMany(SupplierProposal::class, 'agency_id');
    }

    public function isCustomer()
    {
        return $this->user_type === 'Customer';
    }

    public function isAgency()
    {
        return $this->user_type === 'Agency';
    }

    public function isInternal()
    {
        return $this->user_type === 'Internal';
    }

    public function canAccessResource($resource, $action = 'Read')
    {
        return $this->hasPermissionTo("{$resource}.{$action}");
    }
}