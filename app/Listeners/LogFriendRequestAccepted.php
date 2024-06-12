<?php

namespace App\Listeners;

use App\Models\ActivityFeed;
use App\Events\FriendRequestAccepted;

class LogFriendRequestAccepted
{
    public function __construct()
    {
        //
    }

    public function handle(FriendRequestAccepted $event)
    {
        $friendRequest = $event->friendRequest;
        $sender = $event->sender;
        $receiver = $event->receiver;

        ActivityFeed::create([
            'user_id' => $receiver->id,
            'activity_type' => 'friend_request_accepted',
            'description' => "{$receiver->name} accepted a friend request from {$sender->name}",
            'related_id' => $friendRequest->id
        ]);
    }
}
