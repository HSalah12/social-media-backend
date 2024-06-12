<?php

namespace App\Events;

use App\Models\FollowRequest;

class FollowRequestSent
{
    public $followRequest;

    public function __construct(FollowRequest $followRequest)
    {
        $this->followRequest = $followRequest;
    }
}
