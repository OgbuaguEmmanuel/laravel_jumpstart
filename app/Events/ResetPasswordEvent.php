<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Authenticatable $user;
    public string $callbackContactUrl;

    /**
     * Create a new event instance.
     *
     * @param Authenticatable $user
     * @param string $callbackContactUrl
     */
    public function __construct(Authenticatable $user, string $callbackContactUrl)
    {
        $this->user = $user;
        $this->callbackContactUrl = $callbackContactUrl;
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
