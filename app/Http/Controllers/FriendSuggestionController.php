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

        // $suggestedFriends = $user->suggestFriends();
        $user = $request->user();

        // Fetch user IDs of friends
        $users = User::select('name', 'profile_picture', 'gender', 'date_of_birth')
             ->where('id', '!=', $user->id)
             ->get();


        // Use $userIds as needed
        // return response()->json(['userIds' => $userIds]);
        return response()->json([
            'message' => 'Friend suggestions retrieved successfully',
            'suggestions' => $users
        ], 200);
    }
}
