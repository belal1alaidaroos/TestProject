<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkerProblem extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'worker_id',
        'problem_type',
        'description',
        'date_reported',
        'status',
        'resolution_action',
        'resolution_notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date_reported' => 'date',
        'approved_at' => 'datetime',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'approved_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'entity_id')
            ->where('entity_name', 'WorkerProblem');
    }

    public function scopeByWorker($query, $workerId)
    {
        return $query->where('worker_id', $workerId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $problemType)
    {
        return $query->where('problem_type', $problemType);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'Approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'Rejected');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'Closed');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date_reported', [$startDate, $endDate]);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('date_reported', '>=', now()->subDays($days));
    }

    public function approve($approvedBy, $resolutionAction = null, $resolutionNotes = null)
    {
        $this->update([
            'status' => 'Approved',
            'resolution_action' => $resolutionAction,
            'resolution_notes' => $resolutionNotes,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        // Update worker status based on resolution action
        if ($resolutionAction === 'Dismissal') {
            $this->worker->update(['status' => 'Terminated']);
        }
    }

    public function reject($approvedBy, $resolutionNotes = null)
    {
        $this->update([
            'status' => 'Rejected',
            'resolution_notes' => $resolutionNotes,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    public function close($resolutionNotes = null)
    {
        $this->update([
            'status' => 'Closed',
            'resolution_notes' => $resolutionNotes,
        ]);
    }

    public function getProblemTypeLabelAttribute()
    {
        $labels = [
            'escape' => 'Escape',
            'refusal' => 'Refusal to Work',
            'non_compliance' => 'Non-Compliance',
            'misconduct' => 'Misconduct',
            'early_return' => 'Early Return',
        ];

        return $labels[$this->problem_type] ?? $this->problem_type;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'Pending' => 'yellow',
            'Approved' => 'green',
            'Rejected' => 'red',
            'Closed' => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function getResolutionActionLabelAttribute()
    {
        $labels = [
            'Dismissal' => 'Dismissal',
            'Re-training' => 'Re-training',
            'Escalation' => 'Escalation',
        ];

        return $labels[$this->resolution_action] ?? $this->resolution_action;
    }

    public function canBeApproved()
    {
        return $this->status === 'Pending';
    }

    public function canBeRejected()
    {
        return $this->status === 'Pending';
    }

    public function canBeClosed()
    {
        return in_array($this->status, ['Approved', 'Rejected']);
    }
}