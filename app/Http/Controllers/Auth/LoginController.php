<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Hash;
use App\Models\UserStatus;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $user = User::where('email', $credentials['email'])->first();
        
        if ($user && Hash::check($credentials['password'], $user->password)) {
            if (!$user->hasVerifiedEmail()) {
                return response()->json([
                    'error' => 'Email Not Verified',
                    'message' => 'Please verify your email address before logging in.'
                ], 403);
            }

            $token = $user->createToken('myToken')->accessToken;
            $user->update(['active' => true]);
            
            // Log user login for debugging
            Log::info('User logged in', ['user_id' => $user->id]);

            // Update user status
            $userStatus = UserStatus::updateOrCreate(
                ['user_id' => $user->id],
                ['status' => 'online', 'last_seen_at' => now()]
            );

            if (!$userStatus) {
                // Log status update failure for debugging
                Log::error('Failed to update user status', ['user_id' => $user->id]);
            }

            return response()->json([
                'message' => 'Login successful',
                'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'profile_picture_url' => $user->profile_picture_url,
                'cover_photo_url' => $user->cover_photo_url,
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
                ],
                'token' => $token,
            ], 200);
        }

        return response()->json([
            'error' => 'Unauthorized',
            'message' => 'Email or Password incorrect'
        ], 401);
    }
}
