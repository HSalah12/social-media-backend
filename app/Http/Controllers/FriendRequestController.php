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
        $existingRequest = FriendRequest::where(function($query) use ($sender_id, $receiver_id) {
            $query->where('sender_id', $sender_id)
                  ->where('receiver_id', $receiver_id);
        })->orWhere(function($query) use ($sender_id, $receiver_id) {
            $query->where('sender_id', $receiver_id)
                  ->where('receiver_id', $sender_id);
        })->first();

        if ($existingRequest) {
            return response()->json([
                'message' => 'Friend request already sent',
                'request_id' => $existingRequest->id,
                'status' => $existingRequest->status
            ], 400);
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
            'receiver_id' => $receiver_id,
            'status' => 'waiting for accept'
        ], 200);
    }
    /**
     * Accept a friend request and update the status.
     *
     * @param int $id The ID of the friend request to accept.
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptFriendRequest(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|exists:users,id',
        ]);

        $senderId = $request->input('sender_id');
        $receiverId = Auth::id();

        if (!$receiverId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Start transaction
        DB::beginTransaction();

        try {
            // Fetch the friend request
            $friendRequest = FriendRequest::where('sender_id', $senderId)
                ->where('receiver_id', $receiverId)
                ->where('status', 'pending')
                ->first();

            if (!$friendRequest) {
                return response()->json(['message' => 'Friend request not found'], 404);
            }

            // Update the friend request status
            $friendRequest->update(['status' => 'accepted', 'is_accepted' => true]);

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

            return response()->json(['message' => 'Friend request accepted', 'status' => 'accepted'], 200);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            return response()->json(['message' => 'Failed to accept friend request', 'error' => $e->getMessage()], 500);
        }
    }

    public function rejectFriendRequest(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|exists:users,id'
        ]);

        $senderId = $request->input('sender_id');
        $receiverId = Auth::id();

        if (!$receiverId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        Log::info("Attempting to reject friend request from sender with ID: {$senderId}");

        $friendRequest = FriendRequest::where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->first();

        if (!$friendRequest) {
            Log::error("Friend request not found from sender with ID: {$senderId}");
            return response()->json(['message' => 'Friend request not found'], 404);
        }

        // Optionally, check the relationship status if needed
        // Example: if($friendRequest->status != 'pending') { ... }

        $friendRequest->delete();

        Log::info("Friend request from sender with ID: {$senderId} has been rejected and deleted.");

        return response()->json(['message' => 'Friend request rejected', 'status' => 'rejected'], 200);
    }

    public function checkFriendStatus($friendId)
    {
        $authUserId = Auth::id();

        if (!$authUserId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = User::findOrFail($authUserId);
        $status = $user->getFriendshipStatus($friendId);

        return response()->json(['status' => $status], 200);
    }

    public function getFriends($userId)
    {
        $authUserId = Auth::id();

        if (!$authUserId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Retrieve the user
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Get the list of friends
        $friends = $user->friendss()->get(['id', 'name', 'profile_picture'])->map(function($friend) {
            return [
                'id' => $friend->id,
                'name' => $friend->name,
                'profile_picture_url' => $friend->profile_picture_url,
            ];
        });

        return response()->json($friends);
    }

     // Retrieve friends for the authenticated user
     public function getAuthUserFriends()
     {
         $authUserId = Auth::id();
 
         if (!$authUserId) {
             return response()->json(['message' => 'Unauthorized'], 401);
         }
 
         // Retrieve the authenticated user
         $user = User::find($authUserId);
 
         // Get the list of friends
         $friends = $user->friendss()->get(['id', 'name', 'profile_picture'])->map(function($friend) {
             return [
                 'id' => $friend->id,
                 'name' => $friend->name,
                 'profile_picture_url' => $friend->profile_picture_url,
             ];
         });
 
         return response()->json($friends);
     }
     public function unfriend(Request $request)
     {
         $request->validate([
             'friend_id' => 'required|exists:users,id'
         ]);
 
         $authUserId = Auth::id();
         $friendId = $request->input('friend_id');
 
         if (!$authUserId) {
             return response()->json(['message' => 'Unauthorized'], 401);
         }
 
         try {
             // Start transaction
             DB::beginTransaction();
 
             // Delete the friendship relation
             DB::table('friendships')
                 ->where(function($query) use ($authUserId, $friendId) {
                     $query->where('user_id', $authUserId)
                           ->where('friend_id', $friendId);
                 })
                 ->orWhere(function($query) use ($authUserId, $friendId) {
                     $query->where('user_id', $friendId)
                           ->where('friend_id', $authUserId);
                 })
                 ->delete();
 
              // Delete any related friend requests from the friend_requests table
            DB::table('friend_requests')
            ->where(function($query) use ($authUserId, $friendId) {
                $query->where('sender_id', $authUserId)
                      ->where('receiver_id', $friendId);
            })
            ->orWhere(function($query) use ($authUserId, $friendId) {
                $query->where('sender_id', $friendId)
                      ->where('receiver_id', $authUserId);
            })
            ->delete();

        // Commit the transaction
        DB::commit();
 
             return response()->json(['message' => 'Unfriended successfully'], 200);
         } catch (\Exception $e) {
             // Rollback transaction on error
             DB::rollBack();
             return response()->json(['message' => 'Failed to unfriend', 'error' => $e->getMessage()], 500);
         }
     }

     public function deleteFriendRequest(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id'
        ]);

        $authUserId = Auth::id();
        $receiverId = $request->input('receiver_id');

        if (!$authUserId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            // Start transaction
            DB::beginTransaction();

            // Find the friend request
            $friendRequest = FriendRequest::where(function($query) use ($authUserId, $receiverId) {
                $query->where('sender_id', $authUserId)
                      ->where('receiver_id', $receiverId);
            })->orWhere(function($query) use ($authUserId, $receiverId) {
                $query->where('sender_id', $receiverId)
                      ->where('receiver_id', $authUserId);
            })->first();

            if (!$friendRequest) {
                return response()->json(['message' => 'Friend request not found'], 404);
            }

            // Ensure the authenticated user is either the sender or receiver of the friend request
            if ($friendRequest->sender_id !== $authUserId && $friendRequest->receiver_id !== $authUserId) {
                return response()->json(['message' => 'Unauthorized to delete this friend request'], 403);
            }

            // Delete the friend request
            $friendRequest->delete();

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'Friend request deleted successfully'], 200);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete friend request', 'error' => $e->getMessage()], 500);
        }
    }
}
