<?php

namespace App\Listeners;

use App\Events\FollowRequestSent;
use App\Models\ActivityFeed;

class LogFollowRequestSent
{
    public function handle(FollowRequestSent $event)
    {
        $followRequest = $event->followRequest;

        ActivityFeed::create([
            'user_id' => $event->followRequest->user_id,
            'activity_type' => 'follow_request_sent',
            'related_id' => $followRequest->id,
            'description' => 'Follow request sent to user with ID ' . $event->followRequest->followed_id,
        ]);
    }
}
