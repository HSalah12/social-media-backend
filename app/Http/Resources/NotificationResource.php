<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'created_at' => $this->created_at,
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'profile_picture_url' => $this->sender->profile_picture ? url('storage/' . $this->sender->profile_picture) : null,
            ],
            'receiver' => [
                'id' => $this->receiver->id,
                'name' => $this->receiver->name,
                'profile_picture_url' => $this->receiver->profile_picture ? url('storage/' . $this->receiver->profile_picture) : null,
            ],
        ];
    }
}

