<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Crypt;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Log;
class ConversationController extends Controller
{
    public function createConversation(Request $request)
    {
        $request->validate([
            'user_two_id' => 'required|exists:users,id',
        ]);

        $userOneId = Auth::id();
        $userTwoId = $request->user_two_id;

        $conversation = Conversation::create([
            'user_one_id' => $userOneId,
            'user_two_id' => $userTwoId,
        ]);

        return response()->json($conversation, 201);
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'message' => 'required|string',
        ]);

        $senderId = Auth::id();

        if (!$senderId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Retrieve the conversation to determine the receiver_id
        $conversation = Conversation::findOrFail($validated['conversation_id']);

        // Determine the receiver_id
        $receiverId = ($conversation->user_one_id == $senderId) ? $conversation->user_two_id : $conversation->user_one_id;

        $encryptedMessage = Crypt::encryptString($validated['message']);

        $message = Message::create([
            'conversation_id' => $validated['conversation_id'],
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => $encryptedMessage,
            'is_delivered' => false, 
        ]);

        // Code to send notification to the receiver (e.g., via websockets, push notification, etc.)

        return response()->json([
            'data' => $message, // Encrypted message
            'decrypted_message' => $validated['message'], // Original, unencrypted message
        ], 201);
    }
    

    public function getMessages($conversationId)
{
    $messages = Message::where('conversation_id', $conversationId)->get();

    $messagesWithDecryption = $messages->map(function ($message) {
        try {
            $decryptedMessage = Crypt::decryptString($message->message);
        } catch (\Exception $e) {
            \Log::error('Decryption failed for message ID: ' . $message->id . '. Error: ' . $e->getMessage());
            $decryptedMessage = null;
        }

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'receiver_id' => $message->receiver_id,
            'encrypted_message' => $message->message,
            'decrypted_message' => $decryptedMessage,
            'created_at' => $message->created_at,
            'updated_at' => $message->updated_at,
        ];
    });

    return response()->json($messagesWithDecryption, 200);
}
public function search(Request $request)
{
    $filters = [
        'keyword' => $request->input('keyword', ''),
        'sender_id' => $request->input('sender_id', null),
        'receiver_id' => $request->input('receiver_id', null),
        'start_date' => $request->input('start_date', null),
        'end_date' => $request->input('end_date', null),
    ];

    $messages = Message::searchAndFilter($filters)->get();

    // Decrypt messages
    $messages->each(function ($message) {
        try {
            $message->message = decrypt($message->message);
        } catch (\Exception $e) {
            // Handle decryption error
            Log::error('Decryption failed for message ID: ' . $message->id);
        }
    });

    return response()->json($messages);
}
public function getAllChats()
{
    $userId = Auth::id();

    if (!$userId) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $conversations = Conversation::where('user_one_id', $userId)
        ->orWhere('user_two_id', $userId)
        ->with(['userOne:id,name,profile_picture', 'userTwo:id,name,profile_picture', 'messages' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(1);
        }])
        ->get();

    $conversationsWithLatestMessage = $conversations->map(function ($conversation) {
        return [
            'conversation_id' => $conversation->id,
            'user_one' => $conversation->userOne,
            'user_two' => $conversation->userTwo,
            'latest_message' => $conversation->messages->first(),
        ];
    });

    return response()->json($conversationsWithLatestMessage);
}
}
