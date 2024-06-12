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
            'description' => "{$receiver->name} rejected a friend request from {$sender->name}",
            'related_id' => $friendRequest->id
        ]);
    }
}
