<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Storage; 
class LikedUserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            
                'id' => $this->id,
                'name' => $this->name,
                'profile_picture_url' => $this->profile_picture ? Storage::disk('public')->url($this->profile_picture) : null,
            
           
        ];
    }
}
