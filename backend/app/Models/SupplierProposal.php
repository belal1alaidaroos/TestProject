<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierProposal extends BaseModel
{
    protected $fillable = [
        'request_id',
        'agency_id',
        'offered_qty',
        'unit_price',
        'lead_time_days',
        'valid_until',
        'notes',
        'attachment_path',
        'status',
        'approved_qty',
        'approved_by',
        'approved_at',
        'admin_notes',
    ];

    protected $casts = [
        'offered_qty' => 'integer',
        'unit_price' => 'decimal:2',
        'lead_time_days' => 'integer',
        'approved_qty' => 'integer',
        'valid_until' => 'datetime',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequest::class, 'request_id');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'approved_by');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'Submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'Approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'Rejected');
    }

    public function scopeByAgency($query, $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeByRequest($query, $requestId)
    {
        return $query->where('request_id', $requestId);
    }

    public function getTotalAmountAttribute()
    {
        return $this->offered_qty * $this->unit_price;
    }

    public function getApprovedAmountAttribute()
    {
        return ($this->approved_qty ?? 0) * $this->unit_price;
    }
}