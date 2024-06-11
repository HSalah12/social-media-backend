<?php

// app/Http/Controllers/FollowRequestController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FollowRequest;
use App\Models\User;
use Auth;
use Illuminate\Support\Facades\DB;

class FollowRequestController extends Controller
{
    public function send(Request $request)
{
    $request->validate([
        'follower_id' => 'required',
        'followed_id' => 'required',
    ]);

    $followRequest = new FollowRequest([
        'user_id' => $request->follower_id,
        'follower_id' => $request->follower_id,
        'followed_id' => $request->followed_id,
        'status' => 'pending',
    ]);

    $user = Auth::user(); 
    $token = $user->createToken('myToken')->accessToken;
    $followRequest->save();

    return response()->json([
        'message' => 'Follow request sent successfully',
        'follow_request_id' => $followRequest->id,
        'token' => $token,
        'followed_id' => $request->followed_id // Return the followed_id
    ]);
}

    public function accept($id)
    {
        // Find the follow request
        $followRequest = FollowRequest::findOrFail($id);
    
        // Update the status to 'accepted'
        $followRequest->update(['status' => 'accepted']);
    
        // Get the user who sent the follow request
        $user = Auth::user(); 
    
        // Create a new token
        $token = $user->createToken('myToken')->accessToken;
    
        // Save the followRequest and followed_id
        $followRequest->status = 'accepted';
       
    
        // Find the user who sent the follow request
        // $user = User::findOrFail($followRequest->user_id);
    
        // Update the user's followings
        $user->follows()->attach($followRequest->user_id);
        $user->followings()->attach($followRequest->followed_id);
        $followRequest->save();
    // dd( $followRequest);
        return response()->json(['message' => 'Follow request accepted', 'token' => $token]);
    }
    
    
    public function reject($id)
    {
        // Find the follow request
        $followRequest = FollowRequest::findOrFail($id);

        // Update the status to 'rejected'
        $followRequest->update(['status' => 'rejected']);
        $user = Auth::user(); 
        $token = $user->createToken('myToken')->accessToken;
        return response()->json(['message' => 'Follow request rejected', 'token' => $token]);
    }

    public function unfollow($id)
    {
        // Find the follow request
        $followRequest = FollowRequest::findOrFail($id);
    
        // Delete the follow request
        $followRequest->delete();
    
        return response()->json(['message' => 'Unfollowed successfully']);
    }
    public function checkFollowStatus(User $user)
    {
        $follower = Auth::user();

        $isFollowing = $follower->followings()->where('followed_id', $user->id)->exists();

        return response()->json(['isFollowing' => $isFollowing]);
    }

    
}
