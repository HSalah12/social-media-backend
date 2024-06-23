<?php

namespace App\Http\Controllers;

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
    
        $message = Message::create([
            'conversation_id' => $validated['conversation_id'],
            'sender_id' => $validated['sender_id'],
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message'],
        ]);
    
        return response()->json($message, 201);
    }
    

    public function getMessages($conversationId)
    {
        $messages = Message::where('conversation_id', $conversationId)->get();
        return response()->json($messages, 200);
    }
}
