<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FollowRequest;
use App\Models\User;
use App\Models\ActivityFeed;
use App\Models\Follower;
use Auth;
use Illuminate\Support\Facades\DB;
use App\Events\FollowRequestSent;
use App\Events\FollowRequestAccepted;
use App\Events\FollowRequestRejected;
use App\Events\UserUnfollowed;
use Log;

class FollowRequestController extends Controller
{
    public function send(Request $request)
{
    $request->validate([
        'followed_id' => 'required|exists:users,id',
    ]);

    $follower_id = Auth::id();
    if (!$follower_id) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $followed_id = $request->input('followed_id');

    Log::info('Follow request send initiated', ['follower_id' => $follower_id, 'followed_id' => $followed_id]);

    $existingRequest = FollowRequest::where('follower_id', $follower_id)
        ->where('followed_id', $followed_id)
        ->first();

    if ($existingRequest) {
        return response()->json(['message' => 'Follow request already sent', 'request_id' => $existingRequest->id], 400);
    }

    $followRequest = new FollowRequest();
    $followRequest->follower_id = $follower_id;
    $followRequest->followed_id = $followed_id;
    $followRequest->status = 'pending';
    $followRequest->save();

    Log::info('Follow request created', ['followRequest' => $followRequest]);

    ActivityFeed::create([
        'user_id' => $follower_id,
        'activity_type' => 'follow_request_sent',
        'related_id' => $followRequest->id,
        'description' => 'Follow request sent to user with ID ' . $followed_id,
    ]);

    event(new FollowRequestSent($followRequest));

    return response()->json([
        'message' => 'Follow request sent successfully',
        'follow_request_id' => $followRequest->id,
        'sender_status' => 'waiting_for_accept',
        'receiver_status' => 'not_followed'
    ]);
}

public function accept(Request $request)
{
    $request->validate([
        'follower_id' => 'required|exists:users,id'
    ]);

    $followerId = $request->input('follower_id');
    $followedId = Auth::id();

    if (!$followedId) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $followRequest = FollowRequest::where('follower_id', $followerId)
        ->where('followed_id', $followedId)
        ->where('status', 'pending')
        ->first();

    if (!$followRequest) {
        return response()->json(['message' => 'Follow request not found'], 404);
    }

    // Update the follow request status to accepted
    $followRequest->update(['status' => 'accepted']);

    // Update the follower relation
    Follower::updateOrCreate(
        [
            'follower_id' => $followerId,
            'followed_id' => $followedId
        ],
        [
            'status' => 'accepted',
            'is_accepted' => true,
            'updated_at' => now()
        ]
    );

    // Create an activity feed entry
    ActivityFeed::create([
        'user_id' => $followedId,
        'activity_type' => 'follow_request_accepted',
        'related_id' => $followRequest->id,
        'description' => 'Follow request accepted by user with ID ' . $followedId,
    ]);

    // Trigger an event
    event(new FollowRequestAccepted($followRequest));

    return response()->json([
        'message' => 'Follow request accepted',
        'sender_status' => 'accepted'
    ]);
}
    public function reject(Request $request)
    {
        $request->validate([
            'follower_id' => 'required|exists:users,id'
        ]);

        $followerId = $request->input('follower_id');
        $followedId = Auth::id();

        if (!$followedId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $followRequest = FollowRequest::where('follower_id', $followerId)
            ->where('followed_id', $followedId)
            ->first();

        if (!$followRequest) {
            return response()->json(['message' => 'Follow request not found'], 404);
        }

        if (!$followRequest) {
            return response()->json(['message' => 'Follow request not found'], 404);
        }

        // Delete the corresponding follower entry
        Follower::where('follower_id', $followerId)
            ->where('followed_id', $followedId)
            ->delete();

        $followRequest->delete();

        ActivityFeed::create([
            'user_id' => $followerId,
            'activity_type' => 'user_unfollowed',
            'related_id' => $followRequest->id,
            'description' => 'User with ID ' . $followerId . ' rejected follow  user with ID ' . $followedId,
        ]);

        event(new UserUnfollowed($followRequest));

        return response()->json(['message' => 'follow rejected successfully']);
    }

    public function unfollow(Request $request)
    {
        $request->validate([
            'followed_id' => 'required|exists:users,id'
        ]);

        $followedId = $request->input('followed_id');
        $followerId = Auth::id();

        if (!$followerId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $followRequest = FollowRequest::where('follower_id', $followerId)
            ->where('followed_id', $followedId)
            ->first();

        if (!$followRequest) {
            return response()->json(['message' => 'Follow request not found'], 404);
        }

        // Delete the corresponding follower entry
        Follower::where('follower_id', $followerId)
            ->where('followed_id', $followedId)
            ->delete();

        $followRequest->delete();

        ActivityFeed::create([
            'user_id' => $followerId,
            'activity_type' => 'user_unfollowed',
            'related_id' => $followRequest->id,
            'description' => 'User with ID ' . $followerId . ' unfollowed user with ID ' . $followedId,
        ]);

        event(new UserUnfollowed($followRequest));

        return response()->json(['message' => 'Unfollowed successfully']);
    }
    public function checkFollowStatus(User $user)
    {
        $follower = Auth::user();
        if (!$follower) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $isFollowing = Follower::where('follower_id', $follower->id)
            ->where('followed_id', $user->id)
            ->where('is_accepted', true)
            ->exists();

        $isPending = FollowRequest::where('follower_id', $follower->id)
            ->where('followed_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        $status = 'not_following';
        if ($isFollowing) {
            $status = 'following';
        } elseif ($isPending) {
            $status = 'pending';
        }

        return response()->json(['status' => $status]);
    }

    public function getFollowers()
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $followers = Follower::where('followed_id', $userId)
            ->where('is_accepted', true)
            ->with('follower:id,name,profile_picture')
            ->get()
            ->map(function ($follower) {
                return [
                    'id' => $follower->follower->id,
                    'name' => $follower->follower->name,
                    'profile_picture' => $follower->follower->profile_picture_url,
                ];
            });

        return response()->json($followers);
    }
    public function gettFollowers($id)
    {
        // Ensure the user is authenticated
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Retrieve the followers for the specified user ID
        $followers = Follower::where('followed_id', $id)
            ->where('is_accepted', true)
            ->with('follower:id,name,profile_picture')
            ->get()
            ->map(function ($follower) {
                return [
                    'id' => $follower->follower->id,
                    'name' => $follower->follower->name,
                    'profile_picture' => $follower->follower->profile_picture_url,
                ];
            });

        return response()->json($followers);
    }
    public function getFollowed()
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $followed = Follower::where('follower_id', $userId)
            ->where('is_accepted', true)
            ->with('followed:id,name,profile_picture')
            ->get()
            ->map(function ($follow) {
                return [
                    'id' => $follow->followed->id,
                    'name' => $follow->followed->name,
                    'profile_picture' => $follow->followed->profile_picture_url,
                ];
            });

        return response()->json($followed);
    }

    public function gettFollowed($id)
    {

        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $followed = Follower::where('follower_id', $id)
            ->where('is_accepted', true)
            ->with('followed:id,name,profile_picture')
            ->get()
            ->map(function ($follow) {
                return [
                    'id' => $follow->followed->id,
                    'name' => $follow->followed->name,
                    'profile_picture' => $follow->followed->profile_picture_url,
                ];
            });

        return response()->json($followed);
    }
}
