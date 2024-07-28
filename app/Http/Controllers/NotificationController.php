<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Events\NewNotification;
use Illuminate\Support\Facades\Http;

class NotificationController extends Controller
{
    /**
     * Store a newly created notification in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'user_id' => 'required|integer'
        ]);

        // Save notification to database
        $notification = Notification::create([
            'title' => $request->title,
            'message' => $request->message,
            'user_id' => $request->user_id
        ]);

        // Fire the event
        event(new NewNotification($notification));

        // Send notification to WebSocket server
        $this->sendNotificationToWebSocketServer($notification);

        return response()->json($notification, 201);
    }

    /**
     * Display a listing of the notifications.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $notifications = Notification::all();
        return response()->json($notifications);
    }

    private function sendNotificationToWebSocketServer($notification)
    {
        try {
            Http::post('http://192.168.1.22:1338/notifications', [
                'title' => $notification->title,
                'message' => $notification->message,
                'userId' => $notification->user_id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error sending notification to WebSocket server: ' . $e->getMessage());
        }
    }
}
