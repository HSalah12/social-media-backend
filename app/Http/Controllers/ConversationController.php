<?php

namespace App\Http\Controllers;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Models\Message;
use Illuminate\Http\Request;
use Log;
use App\Events\MessageSent;
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

    $conversation = Conversation::findOrFail($validated['conversation_id']);
    $receiverId = ($conversation->user_one_id == $senderId) ? $conversation->user_two_id : $conversation->user_one_id;

    $encryptedMessage = Crypt::encryptString($validated['message']);

    $message = Message::create([
        'conversation_id' => $validated['conversation_id'],
        'sender_id' => $senderId,
        'receiver_id' => $receiverId,
        'message' => $encryptedMessage,
        'is_delivered' => false,
    ]);

    event(new MessageSent($message));

    return response()->json([
        'data' => $message,
        'decrypted_message' => $validated['message'],
    ], 200);
}
    
    public function getConversation($conversationId)
    {
        $conversation = Conversation::find($conversationId);
        return response()->json($conversation);
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

    // Fetch conversations where the current user is involved
    $conversations = Conversation::where('user_one_id', $userId)
        ->orWhere('user_two_id', $userId)
        ->with(['userOne:id,name,profile_picture', 'userTwo:id,name,profile_picture'])
        ->get();

    // Log fetched conversations
    \Log::info('Fetched conversations:', $conversations->toArray());

    // Prepare data for JSON response
    $conversationsWithLatestMessage = $conversations->map(function ($conversation) use ($userId) {
        // Get the latest message for the conversation
        $latestMessage = Message::where('conversation_id', $conversation->id)->latest()->first();
        $decryptedMessage = null;
        $encryptedMessage = null;

        if ($latestMessage) {
            try {
                $decryptedMessage = Crypt::decryptString($latestMessage->message);
                $encryptedMessage = $latestMessage->message;
            } catch (\Exception $e) {
                \Log::error('Decryption failed for message ID: ' . $latestMessage->id . '. Error: ' . $e->getMessage());
                // Handle decryption error gracefully
            }
        }

        // Determine the other user in the conversation
        $otherUser = ($conversation->user_one_id == $userId) ? $conversation->userTwo : $conversation->userOne;

        return [
            'conversation_id' => $conversation->id,
            'user' => [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'profile_picture' => $otherUser->profile_picture ? asset('storage/' . $otherUser->profile_picture) : null,
            ],
            'latest_message' => $decryptedMessage
        ];
    });

    return response()->json($conversationsWithLatestMessage);
}

public function markAsDelivered(Request $request, $id)
{
    $message = Message::findOrFail($id);
    $message->is_delivered = true;
    $message->save();

    return response()->json($message);
}

public function markAsRead(Request $request, $id)
{
    $message = Message::findOrFail($id);
    $message->is_read = true;
    $message->save();

    return response()->json($message);
}


}
