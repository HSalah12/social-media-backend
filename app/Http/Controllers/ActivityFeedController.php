<?php

// app/Http/Controllers/ActivityFeedController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityFeed;

class ActivityFeedController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Not authorized'
            ], 401);
        }

        $activityFeed = ActivityFeed::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Activity feed retrieved successfully',
            'activity_feed' => $activityFeed
        ], 200);
    }
}
