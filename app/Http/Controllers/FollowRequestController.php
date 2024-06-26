<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FollowRequest;
use App\Models\User;
use Auth;
use Illuminate\Support\Facades\DB;
use App\Events\FollowRequestSent;
use App\Events\FollowRequestAccepted;
use App\Events\FollowRequestRejected;
use App\Events\UserUnfollowed;
use App\Models\Follower;
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

        event(new FollowRequestSent($followRequest));

        return response()->json([
            'message' => 'Follow request sent successfully',
            'follow_request_id' => $followRequest->id,
            'token' => $token,
            'followed_id' => $request->followed_id
        ]);
    }

    public function accept($id)
    {
        $followRequest = FollowRequest::findOrFail($id);
        $followRequest->update(['status' => 'accepted']);

        $user = Auth::user();
        $token = $user->createToken('myToken')->accessToken;

        // Update or create the follower relationship in the followers table
        $follower = Follower::updateOrCreate(
            [
                'user_id' => $followRequest->user_id,
                'followed_id' => $followRequest->followed_id
            ],
            [
                'is_accepted' => true
            ]
        );

        event(new FollowRequestAccepted($followRequest));

        return response()->json(['message' => 'Follow request accepted', 'token' => $token]);
    }
    public function reject($id)
    {
        $followRequest = FollowRequest::findOrFail($id);
        $followRequest->update(['status' => 'rejected']);

        $user = Auth::user();
        $token = $user->createToken('myToken')->accessToken;

        event(new FollowRequestRejected($followRequest));

        return response()->json(['message' => 'Follow request rejected', 'token' => $token]);
    }

    public function unfollow($id)
    {
        $followRequest = FollowRequest::findOrFail($id);
        $followRequest->delete();

        event(new UserUnfollowed($followRequest));

        return response()->json(['message' => 'Unfollowed successfully']);
    }

    public function checkFollowStatus(User $user)
    {
        $follower = Auth::user();

        // Check if there's a direct following relationship
        $isFollowing = $follower->isFollowing($user);

        // If not following directly, check if there's a pending follow request that has been accepted
        if (!$isFollowing) {
            $isPending = $follower->followRequests()->where('followed_id', $user->id)->where('status', 'accepted')->exists();
            $isFollowing = $isPending;
        }

        return response()->json(['isFollowing' => $isFollowing]);
    }
}
