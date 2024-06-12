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

        $followRequest->status = 'accepted';
        $followRequest->followed_id = $user->id;
        $followRequest->save();

        event(new FollowRequestAccepted($followRequest));

        $user->follows()->attach($followRequest->user_id);

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
        $isFollowing = $follower->follows()->where('followed_id', $user->id)->exists();

        return response()->json(['isFollowing' => $isFollowing]);
    }
}
