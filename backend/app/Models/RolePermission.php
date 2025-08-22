<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermission extends BaseModel
{
    protected $fillable = [
        'role_id',
        'resource_id',
        'actions',
    ];

    protected $casts = [
        'actions' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(AppRole::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(AppResource::class);
    }

    public function hasAction($action)
    {
        return in_array($action, $this->actions ?? []);
    }

    public function scopeByRole($query, $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    public function scopeByResource($query, $resourceId)
    {
        return $query->where('resource_id', $resourceId);
    }
}