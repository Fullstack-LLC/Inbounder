<?php

declare(strict_types=1);

namespace Inbounder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model for storing outbound email records.
 *
 * This model tracks emails sent through Mailgun and provides
 * a link to webhook events for comprehensive email analytics.
 *
 * @property int $id
 * @property string $message_id
 * @property string $recipient
 * @property string|null $from_address
 * @property string|null $from_name
 * @property string|null $subject
 * @property string|null $template_name
 * @property string|null $campaign_id
 * @property string|null $user_id
 * @property array|null $metadata
 * @property string|null $status
 * @property \Carbon\Carbon|null $sent_at
 * @property \Carbon\Carbon|null $delivered_at
 * @property \Carbon\Carbon|null $opened_at
 * @property \Carbon\Carbon|null $clicked_at
 * @property \Carbon\Carbon|null $bounced_at
 * @property \Carbon\Carbon|null $complained_at
 * @property \Carbon\Carbon|null $unsubscribed_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class MailgunOutboundEmail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mailgun_outbound_emails';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'message_id',
        'recipient',
        'from_address',
        'from_name',
        'subject',
        'template_name',
        'campaign_id',
        'user_id',
        'metadata',
        'status',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'complained_at',
        'unsubscribed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
        'complained_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    /**
     * Get the message ID.
     */
    public function getMessageId(): string
    {
        return $this->message_id;
    }

    /**
     * Get the recipient email address.
     */
    public function getRecipient(): string
    {
        return $this->recipient;
    }

    /**
     * Get the from address.
     */
    public function getFromAddress(): ?string
    {
        return $this->from_address;
    }

    /**
     * Get the from name.
     */
    public function getFromName(): ?string
    {
        return $this->from_name;
    }

    /**
     * Get the subject line.
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * Get the template name.
     */
    public function getTemplateName(): ?string
    {
        return $this->template_name;
    }

    /**
     * Get the campaign ID.
     */
    public function getCampaignId(): ?string
    {
        return $this->campaign_id;
    }

    /**
     * Get the user ID.
     */
    public function getUserId(): ?string
    {
        return $this->user_id;
    }

    /**
     * Get the metadata.
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * Get the current status.
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Get the sent timestamp.
     */
    public function getSentAt(): ?\Carbon\Carbon
    {
        return $this->sent_at;
    }

    /**
     * Get the delivered timestamp.
     */
    public function getDeliveredAt(): ?\Carbon\Carbon
    {
        return $this->delivered_at;
    }

    /**
     * Get the opened timestamp.
     */
    public function getOpenedAt(): ?\Carbon\Carbon
    {
        return $this->opened_at;
    }

    /**
     * Get the clicked timestamp.
     */
    public function getClickedAt(): ?\Carbon\Carbon
    {
        return $this->clicked_at;
    }

    /**
     * Get the bounced timestamp.
     */
    public function getBouncedAt(): ?\Carbon\Carbon
    {
        return $this->bounced_at;
    }

    /**
     * Get the complained timestamp.
     */
    public function getComplainedAt(): ?\Carbon\Carbon
    {
        return $this->complained_at;
    }

    /**
     * Get the unsubscribed timestamp.
     */
    public function getUnsubscribedAt(): ?\Carbon\Carbon
    {
        return $this->unsubscribed_at;
    }

    /**
     * Get all webhook events for this email.
     */
    public function events(): HasMany
    {
        return $this->hasMany(MailgunEvent::class, 'message_id', 'message_id');
    }

    /**
     * Scope a query to only include emails by recipient.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecipient($query, string $recipient)
    {
        return $query->where('recipient', $recipient);
    }

    /**
     * Scope a query to only include emails by campaign.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCampaign($query, string $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Scope a query to only include emails by user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include emails by status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include emails sent in the last 24 hours.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLast24Hours($query)
    {
        return $query->where('sent_at', '>=', now()->subDay());
    }

    /**
     * Scope a query to only include emails sent in the last 7 days.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLast7Days($query)
    {
        return $query->where('sent_at', '>=', now()->subWeek());
    }

    /**
     * Scope a query to only include emails sent in the last 30 days.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLast30Days($query)
    {
        return $query->where('sent_at', '>=', now()->subMonth());
    }
}
