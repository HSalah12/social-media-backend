<?php

namespace App\Listeners;

use App\Models\ActivityFeed;
use App\Events\FriendRequestSent;

class LogFriendRequestSent
{
    public function __construct()
    {
        //
    }

    public function handle(FriendRequestSent $event)
    {
        $friendRequest = $event->friendRequest;
        $sender = $event->sender;
        $receiver = $event->receiver;

        ActivityFeed::create([
            'user_id' => $sender->id,
            'activity_type' => 'friend_request_sent',
            'description' => "{$sender->id} sent a friend request to {$receiver->id}",
            'related_id' => $friendRequest->id
        ]);
    }
}
