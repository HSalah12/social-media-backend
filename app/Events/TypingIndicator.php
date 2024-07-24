<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TypingIndicator implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $conversationId;
    public $isTyping;
    public $otherUserId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($userId, $conversationId, $isTyping, $otherUserId)
    {
        $this->userId = $userId;
        $this->conversationId = $conversationId;
        $this->isTyping = $isTyping;
        $this->otherUserId = $otherUserId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel
     */
    public function broadcastOn()
    {
        return new PrivateChannel('conversation.' . $this->conversationId);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'typing.indicator';
    }
}
