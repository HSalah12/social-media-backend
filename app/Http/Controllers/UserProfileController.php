<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Support\Facades\Hash;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Http\Resources\UserResource;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMediaTrait;
use Illuminate\Support\Facades\Auth;

class UserProfileController extends Controller 
{
    use InteractsWithMedia;

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'date_of_birth' => 'nullable|date_format:Y-m-d',
            'password' => 'required|string|min:8|confirmed',
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'gender' => 'required|in:male,female,other',
            'city' => 'nullable|string|max:255', 
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'bio' => 'nullable|string', 
            'phone_number' => 'nullable|string|max:255', 
            'website_url' => 'nullable|url|max:255',
            'visibility_settings' => 'nullable|string|max:255', 
            'privacy_settings' => 'nullable|string|max:255', 
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
            'social_media_links' => 'nullable|json',
            'notification_preferences' => 'nullable|boolean',
            'security_settings' => 'nullable|boolean',
            'achievements' => 'nullable|boolean',
            'badges' => 'nullable|boolean',
            // Add other validations as needed
        ]);
    
        // Log the validated data
        \Log::info('Validated Data: ', $validatedData);
    
        $user = User::create([
            'name' => $validatedData['name'],
            'username' => $validatedData['username'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'profile_picture' => $validatedData['profile_picture'],
            'date_of_birth' => $validatedData['date_of_birth'],
            'gender' => $validatedData['gender'],
            'city' => $validatedData['city'],
            'state' => $validatedData['state'],
            'country' => $validatedData['country'],
            'bio' => $validatedData['bio'],
            'phone_number' => $validatedData['phone_number'],
            'website_url' => $validatedData['website_url'],
            'social_media_links' => $validatedData['social_media_links'],
            'visibility_settings' => $validatedData['visibility_settings'],
            'privacy_settings' => $validatedData['privacy_settings'],
            'hobbies' => $validatedData['hobbies'],
            'favorite_books' => $validatedData['favorite_books'],
            'favorite_movies' => $validatedData['favorite_movies'],
            'favorite_music' => $validatedData['favorite_music'],
            'languages_spoken' => $validatedData['languages_spoken'],
            'favorite_quotes' => $validatedData['favorite_quotes'],
            'education_history' => $validatedData['education_history'],
            'employment_history' => $validatedData['employment_history'],
            'relationship_status' => $validatedData['relationship_status'],
            'activity_engagement' => $validatedData['activity_engagement'],
            'notification_preferences' => $validatedData['notification_preferences'],
            'security_settings' => $validatedData['security_settings'],
            'achievements' => $validatedData['achievements'],
            'badges' => $validatedData['badges'] ? 1 : 0,  // Convert boolean to integer
        ]);
    
        return response()->json($user, 201);
    }
    public function update(Request $request, $id)
{
    // Validate the request data
    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'username' => 'required|string|max:255|unique:users',
        'email' => 'required|string|email|max:255|unique:users',
        'date_of_birth' => 'nullable|date_format:Y-m-d',
        'password' => 'required|string|min:8|confirmed',
        'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'gender' => 'required|in:male,female,other',
        'city' => 'nullable|string|max:255', 
        'state' => 'nullable|string|max:255',
        'country' => 'nullable|string|max:255',
        'bio' => 'nullable|string', 
        'phone_number' => 'nullable|string|max:255', 
        'website_url' => 'nullable|url|max:255',
        'visibility_settings' => 'nullable|string|max:255', 
        'privacy_settings' => 'nullable|string|max:255', 
        'hobbies' => 'nullable|string', 
        'favorite_books' => 'nullable|string',
        'favorite_movies' => 'nullable|string', 
        'favorite_music' => 'nullable|string', 
        'languages_spoken' => 'nullable|string', 
        'favorite_quotes' => 'nullable|string', 
        'education_history' => 'nullable|string',
        'employment_history' => 'nullable|string', 
        'relationship_status' => 'nullable|in:single,married,divorced,complicated,other', 
        'activity_engagement' => 'nullable|string', 
        'social_media_links' => 'nullable|json',
        'notification_preferences' => 'nullable|boolean',
        'security_settings' => 'nullable|boolean',
        'achievements' => 'nullable|boolean',
        'badges' => 'nullable|boolean',
        // Add other validations as needed
    ]);
    
    if ($request->hasFile('profile_picture')) {
        $user->addMediaFromRequest('profile_picture')->toMediaCollection('profile_pictures');
    }
    try {
        // Find the user
        $user = User::findOrFail($id);
        
        // Update the user's profile
        $user->update($validatedData);

        return response()->json(['message' => 'Profile updated successfully', 'user' => $user], 200);
    } catch (\Exception $e) {
        // Handle any errors that occur during the update
        return response()->json(['message' => 'Failed to update profile', 'error' => $e->getMessage()], 500);
    }
}


    public function show($id)
    {
        $user = User::findOrFail($id);
        
        return response()->json([
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
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
      // Handle profile picture upload
    if ($request->hasFile('profile_picture')) {
        $user->addMediaFromRequest('profile_picture')
            ->toMediaCollection('profile_pictures');
    }
        $user->save();
        return response()->json($user);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'User account deleted successfully.'
        ], 200);
    }


    public function showdata($id)
    {

        
        $user = User::findOrFail($id);
        return new UserResource($user);
    }

   

public function follow(User $user)
{
    auth()->user()->follow($user);
    return response()->json([
        'message' => 'User make Follow successfully.'
    ], 200);
    return back();
}

public function unfollow(User $user)
{
    auth()->user()->unfollow($user);
    return response()->json([
        'message' => 'User make UnFollow successfully.'
    ], 200);
    return back();
}

public function activities(Request $request, $id)
{
    $user = User::findOrFail($id);
$userActivities = UserActivity::where('user_id', $id)
    ->orderBy('created_at', 'desc')
    ->get(['action', 'created_at']);

return response()->json(['user_activities' => $userActivities]);
    return response()->json([
        'user' => $user,
        'activities' => $activities,
    ]);
}


public function deactivate(Request $request)
{
    $user = Auth::user();
    $user->update(['active' => false]);

    return response()->json(['message' => 'Profile deactivated successfully']);
}

public function remove(Request $request)
{
    $user = User::findOrFail($id);
    $user->delete();

    return response()->json(['message' => 'Profile deleted successfully']);
}


}
