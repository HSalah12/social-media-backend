<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function update(Request $request)
    {
        $user = Auth::user();
        
        $validatedData = $request->validate([
            'name' => 'string|max:255',
            'username' => 'string|max:255|unique:users,username,' . $user->id,
            'email' => 'email|max:255|unique:users,email,' . $user->id,
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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
            'relationship_status' => 'nullable|in:single,in_a_relationship,married,divorced,widowed',
            'activity_engagement' => 'nullable|string',
            'notification_preferences' => 'nullable|json',
            'security_settings' => 'nullable|json',
            'achievements' => 'nullable|json',
            'badges' => 'nullable|boolean',
        ]);

        if ($request->hasFile('profile_picture')) {
            // Delete old profile picture if exists
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            $profilePicturePath = $request->file('profile_picture')->store('profile_pictures', 'public');
            $validatedData['profile_picture'] = $profilePicturePath;
        }

        if ($request->hasFile('cover_photo')) {
            // Delete old cover photo if exists
            if ($user->cover_photo) {
                Storage::disk('public')->delete($user->cover_photo);
            }

            $coverPhotoPath = $request->file('cover_photo')->store('cover_photos', 'public');
            $validatedData['cover_photo'] = $coverPhotoPath;
        }

        $user->update($validatedData);

        return response()->json(['message' => 'User data updated successfully', 'user' => $user]);
    }

    public function friendStatus($userId, $friendId)
    {
        $user = User::findOrFail($userId);
        $friend = User::findOrFail($friendId);

        $isFriend = $user->isFriendWith($friend);

        return response()->json(['isFriend' => $isFriend]);
    }

    public function getOnlineUsers()
    {
        $onlineUsers = UserStatus::getOnlineUsers();

        return response()->json(['online_users' => $onlineUsers]);
    }
}
