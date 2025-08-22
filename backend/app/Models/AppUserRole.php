<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppUserRole extends BaseModel
{
    protected $fillable = [
        'user_id',
        'role_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(AppRole::class);
    }
}