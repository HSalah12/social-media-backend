<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FriendSuggestionController extends Controller
{
    public function suggest(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Not authorized'
            ], 401);
        }

        // Get IDs of friends of the authenticated user
        $friendIds = $user->friends()->pluck('users.id')->toArray();

        // Get IDs of friends of friends
        $friendsOfFriendsIds = User::whereIn('id', function($query) use ($friendIds) {
            $query->select('friend_id')
                  ->from('friendships')
                  ->whereIn('user_id', $friendIds)
                  ->where('status', 'friend');
        })->orWhereIn('id', function($query) use ($friendIds) {
            $query->select('user_id')
                  ->from('friendships')
                  ->whereIn('friend_id', $friendIds)
                  ->where('status', 'friend');
        })
        ->pluck('id')
        ->toArray();

        // Exclude authenticated user's friends and the user themselves
        $suggestedFriendIds = array_diff($friendsOfFriendsIds, $friendIds, [$user->id]);

        // Fetch the suggested friends
        $suggestedFriends = User::whereIn('id', $suggestedFriendIds)
            ->select('id', 'name', 'profile_picture', 'gender', 'date_of_birth')
            ->get();

        return response()->json([
            'message' => 'Friend suggestions retrieved successfully',
            'suggestions' => $suggestedFriends
        ], 200);
    }
}
