<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use WebSocket\Client;
use App\Listeners\SendMessageNotification;

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

        $receiver = User::find($request->receiver_id);
            new SendMessageNotification([
                'title' => $notification->title,
                'message' => $notification->message,
                'fcmToken' => $receiver->fcm_token
            ]);
        return response()->json($notification, 201);
    }

    public function userNotifications()
    {
        $userId = auth()->id();
        Log::info('Fetching notifications for user ID: ' . $userId);

        $notifications = Notification::where('receiver_id', $userId)
            ->where('sender_id', '!=', $userId)
            ->with('sender:id,name,profile_picture')
            ->get();

        Log::info('Notifications retrieved: ' . $notifications->toJson());

        return response()->json($notifications);
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

    private function sendNotificationToWebSocketServer($payload)
    {
        try {
            $http = new Client('http://192.168.1.22:1338/');
            $http->send(json_encode([
                'type' => 'sendNotification',
                'payload' => $payload
            ]));
            $http->close();
        } catch (\Exception $e) {
            Log::error('Error sending notification to WebSocket server: ' . $e->getMessage());
        }
    }
}
