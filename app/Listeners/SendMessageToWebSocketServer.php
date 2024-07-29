<?php

namespace App\Listeners;

use App\Events\UserActionOccurred;
use Illuminate\Support\Facades\Http;

class SendMessageToWebSocketServer
{
    public function handle(UserActionOccurred $event)
    {
        // Send message to WebSocket server
        Http::post('http://192.168.1.22:1338/conversations/send-messag', [
            'message' => $event->message,
            'sender_id' => $event->userId
        ]);
       
    }
}
