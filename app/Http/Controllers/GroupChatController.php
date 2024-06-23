<?php

namespace App\Http\Controllers;

use App\Models\GroupChat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;

class GroupChatController extends Controller
{
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $groupChat = GroupChat::create($request->all());
        return response()->json($groupChat, 201);
    }

    public function sendMessage(Request $request)
{
    // Validate the request data
    $validatedData = $request->validate([
        'group_chat_id' => 'required|exists:group_chats,id',
        'message' => 'required|string',
        'sender_id' => 'required|exists:users,id',
    ]);

    // Encrypt the message content
    $encryptedMessage = Crypt::encryptString($validatedData['message']);

    // Create a new message
    $message = new Message();
    $message->group_chat_id = $validatedData['group_chat_id'];
    $message->message = $encryptedMessage;
    $message->sender_id = $validatedData['sender_id'];
    $message->save();

    return response()->json([
        'encrypted_message' => $message->message, // Encrypted message
        'decrypted_message' => $validatedData['message'], // Original, unencrypted message
        'message' => 'Message sent successfully',
        'sender_id'=>$message->sender_id 
    ], 201);
}

    public function getMessages(GroupChat $groupChat)
    {
        return response()->json($groupChat->messages, 200);
    }
}
