<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\User;

use Illuminate\Http\Request;
use Auth;
class FriendRequestController extends Controller
{

    public function sendFriendRequest(Request $request)
{
    $request->validate([
        'receiver_id' => 'required|exists:users,id',
    ]);

    $sender_id = Auth::id();
    $receiver_id = $request->input('receiver_id');

    // Check if a friend request already exists
    $existingRequest = FriendRequest::where('sender_id', $sender_id)
        ->where('receiver_id', $receiver_id)
        ->first();

    if ($existingRequest) {
        return response()->json(['message' => 'Friend request already sent'], 400);
    }

    // Create a new friend request
    $friendRequest = new FriendRequest();
    $friendRequest->sender_id = $sender_id;
    $friendRequest->receiver_id = $receiver_id;
    $friendRequest->save();

    return response()->json(['message' => 'Friend request sent'], 200);
}
 
    public function acceptFriendRequest($id)
    {
        $friendRequest = FriendRequest::find($id);
    
        if (!$friendRequest) {
            return response()->json(['message' => 'Friend request not found'], 404);
        }
        $friendRequest->status = 'accepted';
       
        $friendRequest->is_accepted = true;
        $friendRequest->save();
       
        $user = User::findOrFail($friendRequest->receiver_id);

        $user->friends()->attach($friendRequest->sender_id);
        $user->friends()->attach($friendRequest->sender_id, ['is_accepted' => true]);

        // Update friendships table here (create a new friendship)
    
        return response()->json(['message' => 'Friend request accepted'], 200);
    }
    
    public function rejectFriendRequest($id)
    {
        $friendRequest = FriendRequest::find($id);
    
        if (!$friendRequest) {
            return response()->json(['message' => 'Friend request not found'], 404);
        }
    
        $friendRequest->delete();
        if ($friendRequest->sender_id && $friendRequest->receiver_id) {
            $user = User::findOrFail($friendRequest->sender_id);
            $user->friends()->detach($friendRequest->receiver_id);
            $user = User::findOrFail($friendRequest->receiver_id);
            $user->friends()->detach($friendRequest->sender_id);
        }
    
        $friendRequest->delete();
        return response()->json(['message' => 'Friend request rejected'], 200);
    }
    
}