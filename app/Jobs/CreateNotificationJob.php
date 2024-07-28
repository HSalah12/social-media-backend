<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $title;
    protected $message;

    /**
     * Create a new job instance.
     *
     * @param int $userId
     * @param string $title
     * @param string $message
     * @return void
     */
    public function __construct($userId, $title, $message)
    {
        $this->userId = $userId;
        $this->title = $title;
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Notification::create([
            'user_id' => $this->userId,
            'title' => $this->title,
            'message' => $this->message,
        ]);
    }
}
