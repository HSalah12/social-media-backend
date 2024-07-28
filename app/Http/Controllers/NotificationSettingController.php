<?php

namespace App\Http\Controllers;

use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationSettingController extends Controller
{
    /**
     * Display the user's notification settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        $user = Auth::user();
        $settings = $user->notificationSettings;
        
        return response()->json($settings);
    }

    /**
     * Update the user's notification settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $request->validate([
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'push_notifications' => 'boolean',
        ]);

        $user = Auth::user();
        $settings = $user->notificationSettings;

        $settings->update($request->only([
            'email_notifications',
            'sms_notifications',
            'push_notifications',
        ]));

        return response()->json(['message' => 'Notification settings updated successfully.']);
    }
    function sendNotification($userId, $title, $message)
{
    $user = User::find($userId);
    $settings = $user->notificationSettings;

    if ($settings->email_notifications) {
        // Send email notification
    }

    if ($settings->sms_notifications) {
        // Send SMS notification
    }

    if ($settings->push_notifications) {
        // Send push notification
    }
}
}
