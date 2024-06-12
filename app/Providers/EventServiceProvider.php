<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\FriendRequestSent;
use App\Listeners\LogFriendRequestSent;
use App\Events\FriendRequestAccepted;
use App\Listeners\LogFriendRequestAccepted;
use App\Events\FriendRequestRejected;
use App\Listeners\LogFriendRequestRejected;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        FriendRequestSent::class => [
            LogFriendRequestSent::class,
        ],
        FriendRequestAccepted::class => [
            LogFriendRequestAccepted::class,
        ],
        FriendRequestRejected::class => [
            LogFriendRequestRejected::class,
        ],
    ];

    public function boot()
    {
        parent::boot();
    }
}
