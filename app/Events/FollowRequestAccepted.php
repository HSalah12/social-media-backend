<?php

namespace App\Events;

use App\Models\FollowRequest;

class FollowRequestAccepted
{
    public $followRequest;

    public function __construct(FollowRequest $followRequest)
    {
        $this->followRequest = $followRequest;
    }
}

