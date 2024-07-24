<?php

namespace App\Listeners;

use App\Events\MessageSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Log;

class SendMessageNotification
{
    public function handle(MessageSent $event)
    {
        $decryptedMessage = $event->decryptedMessage;

        $response = Http::post('http://192.168.1.22:1338/conversations/send-messag', [
            'message' => $decryptedMessage,
            'sender_id' => $event->userId

        ]);

        if ($response->failed()) {
            Log::error('Failed to send message to WebSocket server', [
                'response' => $response->body()
            ]);
        }
    }
}



