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

    /**
     * Get all events for this email.
     */
    public function events(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InboundEmailEvent::class);
    }

    /**
     * Get delivered events count.
     */
    public function delivered(): int
    {
        return $this->events()->where('event_type', 'delivered')->count();
    }

    /**
     * Get opened events count.
     */
    public function opened(): int
    {
        return $this->events()->where('event_type', 'opened')->count();
    }

    /**
     * Get clicked events count.
     */
    public function clicked(): int
    {
        return $this->events()->where('event_type', 'clicked')->count();
    }

    /**
     * Get bounced events count.
     */
    public function bounced(): int
    {
        return $this->events()->where('event_type', 'bounced')->count();
    }

    /**
     * Get dropped events count.
     */
    public function dropped(): int
    {
        return $this->events()->where('event_type', 'dropped')->count();
    }

    /**
     * Get complained events count.
     */
    public function complained(): int
    {
        return $this->events()->where('event_type', 'complained')->count();
    }

    /**
     * Get unsubscribed events count.
     */
    public function unsubscribed(): int
    {
        return $this->events()->where('event_type', 'unsubscribed')->count();
    }

    /**
     * Get failed events count (bounced + dropped).
     */
    public function failed(): int
    {
        return $this->events()->whereIn('event_type', ['bounced', 'dropped'])->count();
    }

    /**
     * Get engagement events count (opened + clicked).
     */
    public function engaged(): int
    {
        return $this->events()->whereIn('event_type', ['opened', 'clicked'])->count();
    }

    /**
     * Check if email was delivered.
     */
    public function wasDelivered(): bool
    {
        return $this->delivered() > 0;
    }

    /**
     * Check if email was opened.
     */
    public function wasOpened(): bool
    {
        return $this->opened() > 0;
    }

    /**
     * Check if email was clicked.
     */
    public function wasClicked(): bool
    {
        return $this->clicked() > 0;
    }

    /**
     * Check if email failed (bounced or dropped).
     */
    public function hasFailed(): bool
    {
        return $this->failed() > 0;
    }

    /**
     * Get open rate as percentage.
     */
    public function getOpenRate(): float
    {
        $recipientCount = $this->getTotalRecipientCount();
        if ($recipientCount === 0) {
            return 0.0;
        }

        return round(($this->opened() / $recipientCount) * 100, 2);
    }

    /**
     * Get click rate as percentage.
     */
    public function getClickRate(): float
    {
        $recipientCount = $this->getTotalRecipientCount();
        if ($recipientCount === 0) {
            return 0.0;
        }

        return round(($this->clicked() / $recipientCount) * 100, 2);
    }

    /**
     * Get bounce rate as percentage.
     */
    public function getBounceRate(): float
    {
        $recipientCount = $this->getTotalRecipientCount();
        if ($recipientCount === 0) {
            return 0.0;
        }

        return round(($this->bounced() / $recipientCount) * 100, 2);
    }

    /**
     * Get failure rate as percentage.
     */
    public function getFailureRate(): float
    {
        $recipientCount = $this->getTotalRecipientCount();
        if ($recipientCount === 0) {
            return 0.0;
        }

        return round(($this->failed() / $recipientCount) * 100, 2);
    }

    /**
     * Get comprehensive statistics for this email.
     */
    public function stats(): array
    {
        $recipientCount = $this->getTotalRecipientCount();

        return [
            'recipient_count' => $recipientCount,
            'events' => [
                'delivered' => $this->delivered(),
                'opened' => $this->opened(),
                'clicked' => $this->clicked(),
                'bounced' => $this->bounced(),
                'dropped' => $this->dropped(),
                'complained' => $this->complained(),
                'unsubscribed' => $this->unsubscribed(),
                'failed' => $this->failed(),
                'engaged' => $this->engaged(),
            ],
            'rates' => [
                'open_rate' => $this->getOpenRate(),
                'click_rate' => $this->getClickRate(),
                'bounce_rate' => $this->getBounceRate(),
                'failure_rate' => $this->getFailureRate(),
            ],
            'status' => [
                'was_delivered' => $this->wasDelivered(),
                'was_opened' => $this->wasOpened(),
                'was_clicked' => $this->wasClicked(),
                'failed' => $this->hasFailed(),
            ],
            'first_event_at' => $this->events()->min('occurred_at'),
            'last_event_at' => $this->events()->max('occurred_at'),
            'total_events' => $this->events()->count(),
        ];
    }

    /**
     * Get events by type.
     */
    public function getEventsByType(string $eventType): \Illuminate\Database\Eloquent\Collection
    {
        return $this->events()->where('event_type', $eventType)->get();
    }

    /**
     * Get first event of a specific type.
     */
    public function getFirstEvent(string $eventType): ?InboundEmailEvent
    {
        return $this->events()->where('event_type', $eventType)->first();
    }

    /**
     * Get last event of a specific type.
     */
    public function getLastEvent(string $eventType): ?InboundEmailEvent
    {
        return $this->events()->where('event_type', $eventType)->latest('occurred_at')->first();
    }

    /**
     * Get all events with geographic data.
     */
    public function getGeographicEvents(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->events()
            ->whereNotNull('country')
            ->select('country', 'region', 'city', 'event_type')
            ->get();
    }

    /**
     * Get all events with device data.
     */
    public function getDeviceEvents(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->events()
            ->whereNotNull('device_type')
            ->select('device_type', 'client_type', 'client_name', 'event_type')
            ->get();
    }
}
