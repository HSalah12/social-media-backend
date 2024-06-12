<?php


namespace App\Events;

use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendRequestSent
{
    use Dispatchable, SerializesModels;

    public $friendRequest;
    public $sender;
    public $receiver;

    public function __construct(FriendRequest $friendRequest, User $sender, User $receiver)
    {
        $this->friendRequest = $friendRequest;
        $this->sender = $sender;
        $this->receiver = $receiver;
    }
}
