<?php

declare(strict_types=1);

namespace Inbounder\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a Mailgun webhook is received.
 *
 * This event is dispatched for webhook events that are configured
 * to trigger events in the mailgun configuration.
 */
class WebhookEventReceived
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $eventType  The type of webhook event (e.g., 'delivered', 'bounced', 'opened')
     * @param  array  $webhookData  The complete webhook data from Mailgun
     * @param  array  $parsedData  The parsed and cleaned webhook data
     */
    public function __construct(
        public readonly string $eventType,
        public readonly array $webhookData,
        public readonly array $parsedData
    ) {}

    /**
     * Get the message ID from the webhook data.
     */
    public function getMessageId(): ?string
    {
        return $this->parsedData['message_id'] ?? null;
    }

    /**
     * Get the recipient email address.
     */
    public function getRecipient(): ?string
    {
        return $this->parsedData['recipient'] ?? null;
    }

    /**
     * Get the domain from the webhook data.
     */
    public function getDomain(): ?string
    {
        return $this->parsedData['domain'] ?? null;
    }

    /**
     * Get the IP address from the webhook data.
     */
    public function getIp(): ?string
    {
        return $this->parsedData['ip'] ?? null;
    }

    /**
     * Get the user agent from the webhook data.
     */
    public function getUserAgent(): ?string
    {
        return $this->parsedData['user_agent'] ?? null;
    }

    /**
     * Get the timestamp from the webhook data.
     */
    public function getTimestamp(): ?string
    {
        return $this->parsedData['timestamp'] ?? null;
    }

    /**
     * Get the reason for bounce/complaint events.
     */
    public function getReason(): ?string
    {
        return $this->parsedData['reason'] ?? null;
    }

    /**
     * Get the error code for bounce events.
     */
    public function getCode(): ?string
    {
        return $this->parsedData['code'] ?? null;
    }

    /**
     * Get the severity level for error events.
     */
    public function getSeverity(): ?string
    {
        return $this->parsedData['severity'] ?? null;
    }

    /**
     * Get the delivery status.
     */
    public function getDeliveryStatus(): ?string
    {
        return $this->parsedData['delivery_status'] ?? null;
    }

    /**
     * Get the envelope data.
     */
    public function getEnvelope(): ?array
    {
        return $this->parsedData['envelope'] ?? null;
    }

    /**
     * Get the flags data.
     */
    public function getFlags(): ?array
    {
        return $this->parsedData['flags'] ?? null;
    }

    /**
     * Get the tags data.
     */
    public function getTags(): ?array
    {
        return $this->parsedData['tags'] ?? null;
    }

    /**
     * Get the campaigns data.
     */
    public function getCampaigns(): ?array
    {
        return $this->parsedData['campaigns'] ?? null;
    }

    /**
     * Get the user variables.
     */
    public function getUserVariables(): ?array
    {
        return $this->parsedData['user_variables'] ?? null;
    }

    /**
     * Get the geolocation data.
     */
    public function getGeolocation(): array
    {
        return [
            'country' => $this->parsedData['country'] ?? null,
            'region' => $this->parsedData['region'] ?? null,
            'city' => $this->parsedData['city'] ?? null,
        ];
    }

    /**
     * Get the client information.
     */
    public function getClientInfo(): array
    {
        return [
            'user_agent' => $this->parsedData['user_agent'] ?? null,
            'device_type' => $this->parsedData['device_type'] ?? null,
            'client_type' => $this->parsedData['client_type'] ?? null,
            'client_name' => $this->parsedData['client_name'] ?? null,
            'client_os' => $this->parsedData['client_os'] ?? null,
        ];
    }

    /**
     * Check if this is a delivery event.
     */
    public function isDeliveryEvent(): bool
    {
        return in_array($this->eventType, ['delivered', 'bounced', 'complained']);
    }

    /**
     * Check if this is an engagement event.
     */
    public function isEngagementEvent(): bool
    {
        return in_array($this->eventType, ['opened', 'clicked', 'unsubscribed']);
    }

    /**
     * Check if this is an error event.
     */
    public function isErrorEvent(): bool
    {
        return in_array($this->eventType, ['bounced', 'complained']);
    }
}
