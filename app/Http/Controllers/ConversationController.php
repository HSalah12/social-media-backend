<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Crypt;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        'sender_id' => 'required|exists:users,id',
        'receiver_id' => 'required|exists:users,id',
        'message' => 'required|string',
    ]);

    $encryptedMessage = Crypt::encryptString($validated['message']);

    $message = Message::create([
        'conversation_id' => $validated['conversation_id'],
        'sender_id' => $validated['sender_id'],
        'receiver_id' => $validated['receiver_id'],
        'message' => $encryptedMessage,
        'is_delivered' => false, // Default value is false
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

    return response()->json($messages);
}
}
