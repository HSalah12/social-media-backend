<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use DB ;

class UserController extends Controller
{
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'string|max:255',
            'username' => 'string|max:255|unique:users,username,' . $user->id,
            'email' => 'email|max:255|unique:users,email,' . $user->id,
            'profile_picture' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'bio' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'website_url' => 'nullable|string',
            'social_media_links' => 'nullable|json',
            'visibility_settings' => 'nullable|string',
            'privacy_settings' => 'nullable|string',
            'hobbies' => 'nullable|string',
            'favorite_books' => 'nullable|string',
            'favorite_movies' => 'nullable|string',
            'favorite_music' => 'nullable|string',
            'languages_spoken' => 'nullable|string',
            'favorite_quotes' => 'nullable|string',
            'education_history' => 'nullable|string',
            'employment_history' => 'nullable|string',
            'relationship_status' => 'nullable|in:0,1,2,3,4,5',
            'activity_engagement' => 'nullable|string',
            'notification_preferences' => 'nullable|json',
            'security_settings' => 'nullable|json',
            'achievements' => 'nullable|json',
            'badges' => 'nullable|boolean',
        ]);

        $user->update($validatedData);
        // DB::commit();
        return response()->json(['message' => 'User data updated successfully', 'user' => $user]);
    }
    public function friendStatus($userId, $friendId)
    {
        $user = User::findOrFail($userId);
        $friend = User::findOrFail($friendId);

        $isFriend = $user->isFriendWith($friend);

        return response()->json(['isFriend' => $isFriend]);
    }
}



