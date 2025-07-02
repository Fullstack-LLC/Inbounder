<?php

declare(strict_types=1);

namespace Inbounder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model for storing Mailgun webhook events.
 *
 * This model represents webhook events received from Mailgun such as
 * delivered, bounced, complained, unsubscribed, opened, and clicked events.
 *
 * @property int $id
 * @property string|null $event
 * @property string|null $message_id
 * @property string|null $recipient
 * @property string|null $domain
 * @property string|null $ip
 * @property string|null $country
 * @property string|null $region
 * @property string|null $city
 * @property string|null $user_agent
 * @property string|null $device_type
 * @property string|null $client_type
 * @property string|null $client_name
 * @property string|null $client_os
 * @property string|null $reason
 * @property string|null $code
 * @property string|null $error
 * @property string|null $severity
 * @property string|null $delivery_status
 * @property string|null $envelope
 * @property string|null $flags
 * @property string|null $tags
 * @property string|null $campaigns
 * @property string|null $user_variables
 * @property string|null $event_timestamp
 * @property array|null $raw_data
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class MailgunEvent extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mailgun_events';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'event',
        'message_id',
        'recipient',
        'domain',
        'ip',
        'country',
        'region',
        'city',
        'user_agent',
        'device_type',
        'client_type',
        'client_name',
        'client_os',
        'reason',
        'code',
        'error',
        'severity',
        'delivery_status',
        'envelope',
        'flags',
        'tags',
        'campaigns',
        'user_variables',
        'event_timestamp',
        'raw_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'envelope' => 'array',
        'flags' => 'array',
        'tags' => 'array',
        'campaigns' => 'array',
        'user_variables' => 'array',
        'raw_data' => 'array',
        'event_timestamp' => 'datetime',
    ];

    /**
     * Get the event type.
     */
    public function getEventType(): ?string
    {
        return $this->event;
    }

    /**
     * Get the message ID.
     */
    public function getMessageId(): ?string
    {
        return $this->message_id;
    }

    /**
     * Get the recipient email address.
     */
    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    /**
     * Get the domain.
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Get the IP address.
     */
    public function getIp(): ?string
    {
        return $this->ip;
    }

    /**
     * Get the country.
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * Get the region.
     */
    public function getRegion(): ?string
    {
        return $this->region;
    }

    /**
     * Get the city.
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * Get the user agent.
     */
    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }

    /**
     * Get the device type.
     */
    public function getDeviceType(): ?string
    {
        return $this->device_type;
    }

    /**
     * Get the client type.
     */
    public function getClientType(): ?string
    {
        return $this->client_type;
    }

    /**
     * Get the client name.
     */
    public function getClientName(): ?string
    {
        return $this->client_name;
    }

    /**
     * Get the client OS.
     */
    public function getClientOs(): ?string
    {
        return $this->client_os;
    }

    /**
     * Get the reason.
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * Get the code.
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Get the error.
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Get the severity.
     */
    public function getSeverity(): ?string
    {
        return $this->severity;
    }

    /**
     * Get the delivery status.
     */
    public function getDeliveryStatus(): ?string
    {
        return $this->delivery_status;
    }

    /**
     * Get the envelope data.
     */
    public function getEnvelope(): ?array
    {
        return $this->envelope;
    }

    /**
     * Get the flags.
     */
    public function getFlags(): ?array
    {
        return $this->flags;
    }

    /**
     * Get the tags.
     */
    public function getTags(): ?array
    {
        return $this->tags;
    }

    /**
     * Get the campaigns.
     */
    public function getCampaigns(): ?array
    {
        return $this->campaigns;
    }

    /**
     * Get the user variables.
     */
    public function getUserVariables(): ?array
    {
        return $this->user_variables;
    }

    /**
     * Get the event timestamp.
     */
    public function getEventTimestamp(): ?\Carbon\Carbon
    {
        return $this->event_timestamp;
    }

    /**
     * Get the raw data.
     */
    public function getRawData(): ?array
    {
        return $this->raw_data;
    }

    /**
     * Scope to filter by event type
     */
    public function scopeEvent($query, $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope to filter by recipient
     */
    public function scopeRecipient($query, $recipient)
    {
        return $query->where('recipient', $recipient);
    }

    /**
     * Scope to filter by message ID
     */
    public function scopeMessageId($query, $messageId)
    {
        return $query->where('message_id', $messageId);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('event_timestamp', [$startDate, $endDate]);
    }

    /**
     * Get events from the last 24 hours
     */
    public function scopeLast24Hours($query)
    {
        return $query->where('event_timestamp', '>=', now()->subDay());
    }

    /**
     * Get events from the last 7 days
     */
    public function scopeLast7Days($query)
    {
        return $query->where('event_timestamp', '>=', now()->subWeek());
    }

    /**
     * Get events from the last 30 days
     */
    public function scopeLast30Days($query)
    {
        return $query->where('event_timestamp', '>=', now()->subMonth());
    }
}
