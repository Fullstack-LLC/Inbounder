<?php

namespace Fullstack\Inbounder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InboundEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'from_email',
        'from_name',
        'to_email',
        'to_name',
        'subject',
        'body_plain',
        'body_html',
        'stripped_text',
        'stripped_html',
        'stripped_signature',
        'sender_id',
        'tenant_id',
        'recipient_count',
        'timestamp',
        'token',
        'signature',
        'domain',
        'message_headers',
        'envelope',
        'attachments_count',
        'size',
    ];

    protected $casts = [
        'message_headers' => 'array',
        'envelope' => 'array',
        'timestamp' => 'datetime',
        'size' => 'integer',
        'attachments_count' => 'integer',
        'recipient_count' => 'integer',
    ];

    /**
     * Get the sender of the email.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'sender_id');
    }

    /**
     * Get the tenant associated with the email.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    /**
     * Get the attachments for the email.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(InboundEmailAttachment::class);
    }

    /**
     * Scope to filter by sender.
     */
    public function scopeBySender($query, int $senderId)
    {
        return $query->where('sender_id', $senderId);
    }

    /**
     * Scope to filter by tenant.
     */
    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by message ID.
     */
    public function scopeByMessageId($query, string $messageId)
    {
        return $query->where('message_id', $messageId);
    }
}
