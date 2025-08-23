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
        'preferred_language',
        'timezone',
        'date_format',
        'currency_preference',
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
        return $this->belongsToMany(AppRole::class, 'app_user_roles', 'app_user_id', 'app_role_id')
                    ->withPivot('assigned_by', 'assigned_at', 'is_primary')
                    ->withTimestamps();
    }

    public function primaryRole()
    {
        return $this->belongsToMany(AppRole::class, 'app_user_roles', 'app_user_id', 'app_role_id')
                    ->wherePivot('is_primary', true)
                    ->first();
    }

    public function assignedRoles()
    {
        return $this->belongsToMany(AppRole::class, 'app_user_roles', 'app_user_id', 'app_role_id')
                    ->withPivot('assigned_by', 'assigned_at', 'is_primary')
                    ->withTimestamps();
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

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(AppUser::class, 'assigned_by');
    }

    public function assignedUsers()
    {
        return $this->hasMany(AppUser::class, 'assigned_by');
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

    public function isAdmin()
    {
        return $this->hasRole('Admin') || $this->hasRole('Super Admin');
    }

    public function isManager()
    {
        return $this->hasRole('Manager') || $this->hasRole('Admin') || $this->hasRole('Super Admin');
    }

    public function isStaff()
    {
        return $this->hasRole('Staff') || $this->hasRole('Manager') || $this->hasRole('Admin') || $this->hasRole('Super Admin');
    }

    public function canAccessResource($resource, $action = 'Read')
    {
        return $this->hasPermissionTo("{$resource}.{$action}");
    }

    public function hasAnyRole($roles)
    {
        if (is_string($roles)) {
            return $this->hasRole($roles);
        }
        
        return $this->hasAnyRole($roles);
    }

    public function hasAllRoles($roles)
    {
        if (is_string($roles)) {
            return $this->hasRole($roles);
        }
        
        return $this->hasAllRoles($roles);
    }

    public function assignRole($role, $assignedBy = null, $isPrimary = false)
    {
        if ($isPrimary) {
            // Remove primary flag from other roles
            $this->roles()->updateExistingPivot($this->roles->pluck('id'), ['is_primary' => false]);
        }

        $this->roles()->attach($role, [
            'assigned_by' => $assignedBy ?? auth()->id(),
            'assigned_at' => now(),
            'is_primary' => $isPrimary,
        ]);
    }

    public function removeRole($role)
    {
        $this->roles()->detach($role);
    }

    public function syncRoles($roles, $assignedBy = null)
    {
        $this->roles()->detach();
        
        foreach ($roles as $role) {
            $this->assignRole($role, $assignedBy);
        }
    }

    public function getPrimaryRoleAttribute()
    {
        return $this->primaryRole();
    }

    public function getRoleNamesAttribute()
    {
        return $this->roles->pluck('name');
    }

    public function getPermissionsAttribute()
    {
        return $this->getAllPermissions()->pluck('name');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByUserType($query, $userType)
    {
        return $query->where('user_type', $userType);
    }

    public function scopeByRole($query, $role)
    {
        return $query->whereHas('roles', function ($q) use ($role) {
            $q->where('name', $role);
        });
    }

    public function scopeByPermission($query, $permission)
    {
        return $query->whereHas('roles.permissions', function ($q) use ($permission) {
            $q->where('name', $permission);
        });
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function getDisplayNameAttribute()
    {
        return $this->name . ' (' . $this->user_type . ')';
    }

    public function getStatusColorAttribute()
    {
        return $this->is_active ? 'green' : 'red';
    }

    public function getLastLoginAttribute()
    {
        // This would need to be implemented based on your login tracking
        return $this->updated_at;
    }
}