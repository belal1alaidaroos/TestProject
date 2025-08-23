<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'entity_name',
        'entity_id',
        'action_type',
        'old_values',
        'new_values',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }

    public function scopeByEntity($query, $entityName, $entityId = null)
    {
        $query->where('entity_name', $entityName);
        
        if ($entityId) {
            $query->where('entity_id', $entityId);
        }
        
        return $query;
    }

    public function scopeByAction($query, $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function getChangesAttribute()
    {
        if ($this->action_type === 'create') {
            return $this->new_values;
        }

        if ($this->action_type === 'delete') {
            return $this->old_values;
        }

        if ($this->action_type === 'update') {
            $changes = [];
            foreach ($this->new_values as $key => $newValue) {
                if (isset($this->old_values[$key]) && $this->old_values[$key] !== $newValue) {
                    $changes[$key] = [
                        'old' => $this->old_values[$key],
                        'new' => $newValue
                    ];
                }
            }
            return $changes;
        }

        return [];
    }

    public function getActionDescriptionAttribute()
    {
        $entityName = ucfirst(str_replace('_', ' ', $this->entity_name));
        
        switch ($this->action_type) {
            case 'create':
                return "Created {$entityName}";
            case 'update':
                return "Updated {$entityName}";
            case 'delete':
                return "Deleted {$entityName}";
            case 'status_change':
                return "Changed {$entityName} status";
            default:
                return ucfirst($this->action_type) . " {$entityName}";
        }
    }
}