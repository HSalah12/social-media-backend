<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Events\NewNotification;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\NotificationResource;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{

    public function index()
    {
        $notifications = Notification::all();
        return response()->json($notifications);
    }
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'receiver_id' => 'required|integer',
            'sender_id' => 'required|integer'
        ]);

        $notification = Notification::create([
            'title' => $request->title,
            'message' => $request->message,
            'receiver_id' => $request->receiver_id,
            'sender_id' => $request->sender_id
        ]);

        event(new NewNotification($notification));
        $this->sendNotificationToWebSocketServer($notification);

        return response()->json($notification, 201);
    }

    public function userNotifications()
    {
        $userId = auth()->id();
        \Log::info('Fetching notifications for user ID: ' . $userId);
    
        // Fetch notifications where the receiver is the current user, and the sender is not the current user
        $notifications = Notification::where('receiver_id', $userId)
            ->where('sender_id', '!=', $userId) // Exclude notifications where the sender is the current user
            ->with('sender:id,name,profile_picture')
            ->get();
    
        \Log::info('Notifications retrieved: ' . $notifications->toJson());
    
        return response()->json(NotificationResource::collection($notifications));
    }
    public function handleTap(Request $request, $id)
{
    $userId = auth()->id();
    if (!$userId) {
        return response()->json(['error' => 'User not authenticated'], 401);
    }

    $notification = Notification::where('id', $id)
                                ->where('receiver_id', $userId)
                                ->first();

    if (!$notification) {
        return response()->json(['error' => 'Notification not found'], 404);
    }

    $notification->markAsRead();

    return response()->json([
        'message' => 'Notification tapped successfully',
        'notification' => $notification,
    ]);
}


    private function sendNotificationToWebSocketServer($notification)
    {
        try {
            Http::post('http://192.168.1.22:1338/notifications', [
                'title' => $notification->title,
                'message' => $notification->message,
                'receiverId' => $notification->receiver_id,
                'senderId' => $notification->sender_id
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending notification to WebSocket server: ' . $e->getMessage());
        }
    }
}
