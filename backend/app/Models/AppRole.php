<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppRole extends BaseModel
{
    protected $fillable = [
        'name_en',
        'name_ar',
        'description_en',
        'description_ar',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(AppUser::class, 'app_user_roles', 'role_id', 'user_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(AppResource::class, 'role_permissions', 'role_id', 'resource_id')
                    ->withPivot('actions')
                    ->withTimestamps();
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(AppUserRole::class, 'role_id');
    }

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'role_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function hasPermission($resource, $action)
    {
        return $this->permissions()
                    ->where('name', $resource)
                    ->whereRaw("JSON_CONTAINS(actions, '\"$action\"')")
                    ->exists();
    }
}