<?php

namespace App\Listeners;

use App\Events\MessageSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Log;

class SendMessageNotification
{
    protected $message;
    public function __construct($message)
    {
       $this->message = $message;

    }
    public function send()
    {
       
    }
}



