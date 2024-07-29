<?php

namespace App\Listeners;

use App\Events\TypingIndicator;
use Illuminate\Support\Facades\Http;

class sendTypingIndicator
{
    public function handle(TypingIndicator $event)
    {
        // Send message to WebSocket server
        Http::post('http://192.168.1.22:1338/typing-indicator', [
            'user_id' => $event->userId,
            'conversation_id' => $event->conversationId,
            'is_typing' => $event->isTyping,
            'other_user_id' => $event->otherUserId
        ]);
        
    }
}
