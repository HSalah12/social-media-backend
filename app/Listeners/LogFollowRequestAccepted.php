<?php

namespace App\Listeners;

use App\Events\FollowRequestAccepted;
use App\Models\ActivityFeed;

class LogFollowRequestAccepted
{
    public function handle(FollowRequestAccepted $event)
    {

        $followRequest = $event->followRequest;
        ActivityFeed::create([
            'user_id' => $event->followRequest->user_id,
            'activity_type' => 'follow_request_accepted',
            'related_id' => $followRequest->id,
            'description' => 'Follow request accepted by user with ID ' . $event->followRequest->followed_id,
        ]);
    }
}
