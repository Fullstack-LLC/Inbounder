<?php

namespace Fullstack\Inbounder\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\WebhookClient\Models\WebhookCall;

class InboundEmailProcessed
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public WebhookCall $webhookCall;

    public array $attachments;

    /**
     * Create a new event instance.
     */
    public function __construct(WebhookCall $webhookCall, array $attachments = [])
    {
        $this->webhookCall = $webhookCall;
        $this->attachments = $attachments;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
