<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TypingIndicator
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $action;
    public $userId;
    public $conversationId;
    public $isTyping;

    public function __construct($action, $userId, $conversationId, $isTyping)
    {
        $this->action = $action;
        $this->userId = $userId;
        $this->conversationId = $conversationId;
        $this->isTyping = $isTyping;
    }
}
