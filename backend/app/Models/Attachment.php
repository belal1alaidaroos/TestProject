<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'entity_name',
        'entity_id',
        'file_name',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'file_extension',
        'description',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'uploaded_by');
    }

    public function scopeByEntity($query, $entityName, $entityId)
    {
        return $query->where('entity_name', $entityName)
                    ->where('entity_id', $entityId);
    }

    public function scopeByType($query, $entityName)
    {
        return $query->where('entity_name', $entityName);
    }

    public function getFileUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }

    public function getFileSizeFormattedAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isImage()
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isPdf()
    {
        return $this->mime_type === 'application/pdf';
    }

    public function isDocument()
    {
        return in_array($this->mime_type, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }
}