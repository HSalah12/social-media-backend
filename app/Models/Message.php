<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['conversation_id', 'sender_id','receiver_id', 'message'];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

   
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }


    public function groupChat()
    {
        return $this->belongsTo(GroupChat::class);
    }
}
