<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAsUnread()
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function getIconAttribute()
    {
        $icons = [
            'new_request' => 'document-plus',
            'approval' => 'check-circle',
            'rejection' => 'x-circle',
            'worker_arrival' => 'user-plus',
            'payment_received' => 'credit-card',
            'contract_created' => 'document-text',
            'proposal_submitted' => 'clipboard-document-list',
            'system_alert' => 'exclamation-triangle',
        ];

        return $icons[$this->type] ?? 'bell';
    }

    public function getColorAttribute()
    {
        $colors = [
            'new_request' => 'blue',
            'approval' => 'green',
            'rejection' => 'red',
            'worker_arrival' => 'green',
            'payment_received' => 'green',
            'contract_created' => 'blue',
            'proposal_submitted' => 'yellow',
            'system_alert' => 'red',
        ];

        return $colors[$this->type] ?? 'gray';
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }
}