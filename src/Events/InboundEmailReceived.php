<?php

declare(strict_types=1);

namespace Inbounder\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a new inbound email is received from Mailgun.
 *
 * This event contains all the parsed email data and can be listened to
 * for custom business logic processing.
 */
class InboundEmailReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The parsed inbound email data.
     */
    public array $emailData;

    /**
     * Create a new event instance.
     *
     * @param  array  $emailData  The parsed inbound email data.
     */
    public function __construct(array $emailData)
    {
        $this->emailData = $emailData;
    }

    /**
     * Get the event name from configuration.
     *
     * @return string The configured event name.
     */
    public static function getEventName(): string
    {
        return config('mailgun.events.inbound_email_received', 'mailgun.inbound.email.received');
    }
}
