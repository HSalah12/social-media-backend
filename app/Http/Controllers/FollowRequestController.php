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
            'follow_request_id' => $followRequest->id
        ]);
    }

    public function accept($id)
    {
        $followRequest = FollowRequest::findOrFail($id);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $followRequest->update(['status' => 'accepted']);

        Follower::updateOrCreate(
            [
                'follower_id' => $followRequest->follower_id,
                'followed_id' => $followRequest->followed_id
            ],
            [
                'is_accepted' => true
            ]
        );

        ActivityFeed::create([
            'user_id' => $user->id,
            'activity_type' => 'follow_request_accepted',
            'related_id' => $followRequest->id,
            'description' => 'Follow request accepted by user with ID ' . $followRequest->followed_id,
        ]);

        event(new FollowRequestAccepted($followRequest));

        return response()->json(['message' => 'Follow request accepted']);
    }

    public function reject($id)
    {
        $followRequest = FollowRequest::findOrFail($id);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $followRequest->update(['status' => 'rejected']);

        ActivityFeed::create([
            'user_id' => $user->id,
            'activity_type' => 'follow_request_rejected',
            'related_id' => $followRequest->id,
            'description' => 'Follow request rejected by user with ID ' . $followRequest->followed_id,
        ]);

        event(new FollowRequestRejected($followRequest));

        return response()->json(['message' => 'Follow request rejected']);
    }

    public function unfollow($id)
    {
        $followRequest = FollowRequest::find($id);

        if (!$followRequest) {
            return response()->json(['message' => 'Follow request not found'], 404);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Delete the corresponding follower entry
        Follower::where('follower_id', $followRequest->follower_id)
            ->where('followed_id', $followRequest->followed_id)
            ->delete();

        $followRequest->delete();

        ActivityFeed::create([
            'user_id' => $user->id,
            'activity_type' => 'user_unfollowed',
            'related_id' => $followRequest->id,
            'description' => 'User with ID ' . $followRequest->follower_id . ' unfollowed user with ID ' . $followRequest->followed_id,
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
}
