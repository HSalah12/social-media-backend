<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\Message;
use Illuminate\Support\Facades\Crypt;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $decryptedMessage;

    public function __construct(Message $message)
    {
        $this->message = $message;
        $this->decryptedMessage = Crypt::decryptString($message->message);
    }

    public function broadcastOn()
    {
        return new Channel('messages');
    }

    public function broadcastWith()
    {
        return ['message' => $this->decryptedMessage];
    }
}
