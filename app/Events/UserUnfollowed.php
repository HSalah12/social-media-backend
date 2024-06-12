<?php

namespace App\Events;

use App\Models\FollowRequest;

class UserUnfollowed
{
    public $followRequest;

    public function __construct(FollowRequest $followRequest)
    {
        $this->followRequest = $followRequest;
    }
}