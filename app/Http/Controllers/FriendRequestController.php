<?php

// app/Http/Controllers/FriendRequestController.php
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
use Illuminate\Support\Facades\DB;
use Log;


class FriendRequestController extends Controller
{
    public function sendFriendRequest(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
        ]);

        $sender_id = Auth::id();
        $receiver_id = $request->input('receiver_id');
        $sender = User::findOrFail($sender_id); // Ensure sender is defined
        $receiver = User::findOrFail($receiver_id); // Ensure receiver is defined
        $existingRequest = FriendRequest::where('sender_id', $sender_id)
            ->where('receiver_id', $receiver_id)
            ->first();

        if ($existingRequest) {
            return response()->json(['message' => 'Friend request already sent', 'request_id' => $existingRequest->id], 400);
        }

        $friendRequest = new FriendRequest();
        $friendRequest->sender_id = $sender_id;
        $friendRequest->receiver_id = $receiver_id;
        $friendRequest->status = 'pending';  // Status set to pending
        $friendRequest->save();

        event(new FriendRequestSent($friendRequest, $sender, $receiver));

        return response()->json([
            'message' => 'Friend request sent',
             'request_id' => $friendRequest->id,
             'receiver_id' => $receiver_id ], 200);
    }

    /**
     * Accept a friend request and update the status.
     *
     * @param int $id The ID of the friend request to accept.
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptFriendRequest(Request $request, $id)
{
    // Start transaction
    DB::beginTransaction();

    try {
        // Fetch the friend request
        $friendRequest = DB::table('friend_requests')
                           ->where('id', $id)
                           ->where('status', 'pending')
                           ->first();

        if (!$friendRequest) {
            return response()->json(['message' => 'Friend request not found'], 404);
        }

        // Update the friend request status
        DB::table('friend_requests')
          ->where('id', $id)
          ->update(['status' => 'friend','is_accepted' => true]);

        // Create or update the friendship relation
        DB::table('friendships')->updateOrInsert(
            ['user_id' => $friendRequest->sender_id, 'friend_id' => $friendRequest->receiver_id],
            ['status' => 'friend', 'updated_at' => now()]  // Assuming you handle created_at in your model or database
        );

        DB::table('friendships')->updateOrInsert(
            ['user_id' => $friendRequest->receiver_id, 'friend_id' => $friendRequest->sender_id],
            ['status' => 'friend', 'updated_at' => now()]
        );

        // Commit the transaction
        DB::commit();

        return response()->json(['message' => 'Friend request accepted'], 200);
    } catch (\Exception $e) {
        // Rollback transaction on error
        DB::rollBack();
        return response()->json(['message' => 'Failed to accept friend request', 'error' => $e->getMessage()], 500);
    }
}

public function rejectFriendRequest($id)
{
    Log::info("Attempting to reject friend request with ID: {$id}");

    $friendRequest = FriendRequest::find($id);
    if (!$friendRequest) {
        Log::error("Friend request not found with ID: {$id}");
        return response()->json(['message' => 'Friend request not found'], 404);
    }

    // Optionally, check the relationship status if needed
    // Example: if($friendRequest->status != 'pending') { ... }

    $friendRequest->delete();

    Log::info("Friend request with ID: {$id} has been rejected and deleted.");

    return response()->json(['message' => 'Friend request rejected'], 200);
}
}
