<?php

namespace App\Listeners;

use App\Events\FollowRequestSent;
use App\Models\ActivityFeed;

class LogFollowRequestSent
{
    public function handle(FollowRequestSent $event)
    {
        ActivityFeed::create([
            'user_id' => $event->followRequest->user_id,
            'activity_type' => 'follow_request_sent',
            'description' => 'Follow request sent to user with ID ' . $event->followRequest->followed_id,
        ]);
    }
}
