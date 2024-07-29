<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class NotificationSettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'email_notifications' => $user->email_notifications, // Adjust according to your user model
            'push_notifications' => $user->push_notifications,
            // Add other notification settings as needed
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validatedData = $request->validate([
            'email_notifications' => 'required|boolean',
            'push_notifications' => 'required|boolean',
            // Add validation rules for other notification settings
        ]);

        $user->update($validatedData);

        return response()->json(['message' => 'Notification settings updated successfully']);
    }
}
