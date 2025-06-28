<?php

namespace Fullstack\Inbounder\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InboundEmailFailed
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public array $emailData;

    public string $error;

    public array $requestData;

    /**
     * Create a new event instance.
     */
    public function __construct(array $emailData, string $error, array $requestData)
    {
        $this->emailData = $emailData;
        $this->error = $error;
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
