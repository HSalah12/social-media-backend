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

        $user->refresh(); // Ensure the updated user object is fetched

        return response()->json([
            'message' => 'User data updated successfully',
            'user' => [
                'profile_picture_url' => $user->profile_picture_url,
                'cover_photo_url' => $user->cover_photo_url,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'date_of_birth' => $user->date_of_birth,
                'gender' => $user->gender,
                'city' => $user->city,
                'state' => $user->state,
                'country' => $user->country,
                'bio' => $user->bio,
                'phone_number' => $user->phone_number,
                'website_url' => $user->website_url,
                'social_media_links' => $user->social_media_links,
                'visibility_settings' => $user->visibility_settings,
                'privacy_settings' => $user->privacy_settings,
                'hobbies' => $user->hobbies,
                'favorite_books' => $user->favorite_books,
                'favorite_movies' => $user->favorite_movies,
                'favorite_music' => $user->favorite_music,
                'languages_spoken' => $user->languages_spoken,
                'favorite_quotes' => $user->favorite_quotes,
                'education_history' => $user->education_history,
                'employment_history' => $user->employment_history,
                'relationship_status' => $user->relationship_status,
                'activity_engagement' => $user->activity_engagement,
                'notification_preferences' => $user->notification_preferences,
                'security_settings' => $user->security_settings,
                'achievements' => $user->achievements,
                'badges' => $user->badges,
            ]
        ]);
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
    public function search(Request $request)
    {
        $query = User::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        if ($request->has('username')) {
            $query->where('username', 'like', '%' . $request->input('username') . '%');
        }

        if ($request->has('email')) {
            $query->where('email', 'like', '%' . $request->input('email') . '%');
        }

        if ($request->has('city')) {
            $query->where('city', 'like', '%' . $request->input('city') . '%');
        }

        if ($request->has('country')) {
            $query->where('country', 'like', '%' . $request->input('country') . '%');
        }

        // Add other filters as needed

        $users = $query->get();
         // Check if the result is empty
         if ($users->isEmpty()) {
            return response()->json(['message' => 'No user found','users' => $users, 'total' => 0], 404);
        }
        $users->each(function ($user) {
            $user->makeHidden('profile_picture');
        });
        $users->each(function ($user) {
            $user->makeHidden('cover_photo');
        });
        $users->each(function ($user) {
            $user->profile_picture_url = $user->profile_picture_url;
        });
        $users->each(function ($user) {
            $user->cover_photo_url = $user->cover_photo_url;
        });
        return response()->json([
            'message' => 'Results :',
            'users' => $users,
             'total' => $users->count(),
            ]
            
            ,200);
    }
}
