<?php

namespace Fullstack\Inbounder\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InboundEmailReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $emailData;
    public array $attachments;
    public array $requestData;

    /**
     * Create a new event instance.
     */
    public function __construct(array $emailData, array $attachments, array $requestData)
    {
        $this->emailData = $emailData;
        $this->attachments = $attachments;
        $this->requestData = $requestData;
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
