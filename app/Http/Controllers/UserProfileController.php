<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserProfileController extends Controller
{
    public function show($id)
    {
        $user = User::findOrFail($id);
        
        return response()->json([
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'profile_picture' => $user->profile_picture,
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
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'User account deleted successfully.'
        ], 200);
    }
}
