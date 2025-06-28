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
        'to_emails',
        'cc_emails',
        'bcc_emails',
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
        'to_emails' => 'array',
        'cc_emails' => 'array',
        'bcc_emails' => 'array',
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
        $userModelClass = config('inbounder.models.user', \App\Models\User::class);

        return $this->belongsTo($userModelClass, 'sender_id');
    }

    /**
     * Get the tenant associated with the email.
     */
    public function tenant(): BelongsTo
    {
        $tenantModelClass = config('inbounder.models.tenant', \App\Models\Tenant::class);

        return $this->belongsTo($tenantModelClass, 'tenant_id');
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

    /**
     * Get all recipient emails (to, cc, bcc combined).
     */
    public function getAllRecipients(): array
    {
        $recipients = [];

        // Add to_emails or fallback to original to_email
        if ($this->to_emails && !empty($this->to_emails)) {
            $recipients = array_merge($recipients, $this->to_emails);
        } elseif ($this->to_email) {
            $recipients[] = $this->to_email;
        }

        if ($this->cc_emails) {
            $recipients = array_merge($recipients, $this->cc_emails);
        }

        if ($this->bcc_emails) {
            $recipients = array_merge($recipients, $this->bcc_emails);
        }

        return array_unique($recipients);
    }

    /**
     * Get the primary recipient (first to email or fallback to original to_email).
     */
    public function getPrimaryRecipient(): ?string
    {
        if ($this->to_emails && !empty($this->to_emails)) {
            return $this->to_emails[0];
        }

        return $this->to_email;
    }

    /**
     * Check if a specific email is a recipient.
     */
    public function isRecipient(string $email): bool
    {
        return in_array($email, $this->getAllRecipients());
    }

    /**
     * Get recipient count including all types.
     */
    public function getTotalRecipientCount(): int
    {
        $count = 0;

        // Count to_emails or fallback to original to_email
        if ($this->to_emails && !empty($this->to_emails)) {
            $count += count($this->to_emails);
        } elseif ($this->to_email) {
            $count += 1;
        }

        // Count cc_emails
        if ($this->cc_emails) {
            $count += count($this->cc_emails);
        }

        // Count bcc_emails
        if ($this->bcc_emails) {
            $count += count($this->bcc_emails);
        }

        return $count;
    }
}
