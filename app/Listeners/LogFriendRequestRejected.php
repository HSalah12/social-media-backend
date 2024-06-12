<?php

namespace App\Listeners;

use App\Models\ActivityFeed;
use App\Events\FriendRequestRejected;

class LogFriendRequestRejected
{
    public function __construct()
    {
        //
    }

    public function handle(FriendRequestRejected $event)
    {
        $friendRequest = $event->friendRequest;
        $sender = $event->sender;
        $receiver = $event->receiver;

        ActivityFeed::create([
            'user_id' => $receiver->id,
            'activity_type' => 'friend_request_rejected',
            'description' => "{$receiver->id} rejected a friend request from {$sender->id}",
            'related_id' => $friendRequest->id
        ]);
    }
}
