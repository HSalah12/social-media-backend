<?php

// app/Http/Controllers/ActivityFeedController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityFeed;
use App\Models\NewsFeedItem; // Import NewsFeedItem model


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
    public function like($id)
    {
        // Logic to like a news feed item
        $newsFeedItem = NewsFeedItem::findOrFail($id);

        // Perform like operation
        // For example:
        $newsFeedItem->likes()->attach(Auth::id());

        return response()->json(['message' => 'News feed item liked']);
    }
    public function unlike($id)
    {
        try {
            // Find the NewsFeedItem by ID or throw a ModelNotFoundException
            $newsFeedItem = NewsFeedItem::findOrFail($id);

            // Perform unlike operation
            // Detach the user's like
            $newsFeedItem->likes()->detach(Auth::id());

            return response()->json(['message' => 'News feed item unliked'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Not Found', 'message' => 'News feed item not found'], 404);
        }
    }
}
