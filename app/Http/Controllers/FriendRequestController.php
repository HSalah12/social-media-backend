<?php

// app/Http/Controllers/FriendRequestController.php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\User;
use App\Events\FriendRequestSent;
use App\Events\FriendRequestAccepted;
use App\Events\FriendRequestRejected;
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

        $sender = User::findOrFail($sender_id);
        $receiver = User::findOrFail($receiver_id);

        // Dispatch the event
        event(new FriendRequestSent($friendRequest, $sender, $receiver));

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

        $sender = User::findOrFail($friendRequest->sender_id);
        $receiver = User::findOrFail($friendRequest->receiver_id);

        $receiver->friends()->attach($sender->id, ['is_accepted' => true]);
        $sender->friends()->attach($receiver->id, ['is_accepted' => true]);

        // Dispatch the event
        event(new FriendRequestAccepted($friendRequest, $sender, $receiver));

        return response()->json(['message' => 'Friend request accepted'], 200);
    }

    public function rejectFriendRequest($id)
    {
        $friendRequest = FriendRequest::find($id);

        if (!$friendRequest) {
            return response()->json(['message' => 'Friend request not found'], 404);
        }

        $sender = User::find($friendRequest->sender_id);
        $receiver = User::find($friendRequest->receiver_id);

        event(new FriendRequestRejected($friendRequest, $sender, $receiver));

        $friendRequest->delete();

        return response()->json(['message' => 'Friend request rejected'], 200);
    }
}
