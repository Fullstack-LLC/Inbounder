<?php

namespace Fullstack\Inbounder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class InboundEmailAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'inbound_email_id',
        'filename',
        'content_type',
        'size',
        'file_path',
        'original_name',
        'disposition',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * Get the email that owns this attachment.
     */
    public function email(): BelongsTo
    {
        return $this->belongsTo(InboundEmail::class, 'inbound_email_id');
    }

    /**
     * Get the full URL to the attachment file.
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get the file size in human readable format.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        // If the unit is MB or higher, show no decimals for whole numbers
        if ($i >= 2 && round($bytes) == $bytes) {
            return (int) $bytes.' '.$units[$i];
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Scope to filter by file size.
     */
    public function scopeBySize($query, int $maxSize)
    {
        return $query->where('size', '<=', $maxSize);
    }

    /**
     * Scope to filter by content type.
     */
    public function scopeByContentType($query, string $contentType)
    {
        return $query->where('content_type', $contentType);
    }
}
