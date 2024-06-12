<?php

// app/Listeners/LogFollowRequestRejected.php

namespace App\Listeners;

use App\Events\FollowRequestRejected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\ActivityFeed;

class LogFollowRequestRejected
{
    public function handle(FollowRequestRejected $event)
{
    ActivityFeed::create([
        'user_id' => $event->followRequest->user_id,
        'activity_type' => 'follow_request_rejected',
        'description' => 'Follow request rejected by user with ID ' . $event->followRequest->followed_id,
    ]);
}
}

