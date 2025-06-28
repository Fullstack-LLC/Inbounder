<?php

namespace Fullstack\Inbounder\Events;

use Fullstack\Inbounder\Models\InboundEmail;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InboundEmailProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public InboundEmail $email;
    public array $attachments;

    /**
     * Create a new event instance.
     */
    public function __construct(InboundEmail $email, array $attachments = [])
    {
        $this->email = $email;
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
