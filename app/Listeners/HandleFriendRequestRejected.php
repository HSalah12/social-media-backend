<?php

namespace App\Listeners;

use App\Events\FriendRequestRejected;
use Illuminate\Support\Facades\Log;

class HandleFriendRequestRejected
{
    /**
     * Handle the event.
     *
     * @param  FriendRequestRejected  $event
     * @return void
     */
    public function handle(FriendRequestRejected $event)
    {
        // Log information or perform other actions like sending notifications
        Log::info("Friend request from {$event->sender->name} to {$event->receiver->name} was rejected.");
        // Optionally, send notifications or update other parts of the system
    }
}
