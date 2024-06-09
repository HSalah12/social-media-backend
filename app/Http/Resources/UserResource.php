<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'bio' => $this->bio,
            'phone_number' => $this->phone_number,
            'website_url' => $this->website_url,
            'social_media_links' => $this->social_media_links,
            'visibility_settings' => $this->visibility_settings,
            'privacy_settings' => $this->privacy_settings,
            'hobbies' => $this->hobbies,
            'favorite_books' => $this->favorite_books,
            'favorite_movies' => $this->favorite_movies,
            'favorite_music' => $this->favorite_music,
            'languages_spoken' => $this->languages_spoken,
            'favorite_quotes' => $this->favorite_quotes,
            'education_history' => $this->education_history,
            'employment_history' => $this->employment_history,
            'relationship_status' => $this->relationship_status,
            'activity_engagement' => $this->activity_engagement,
            'notification_preferences' => $this->notification_preferences,
            'security_settings' => $this->security_settings,
            'achievements' => $this->achievements,
            'badges' => $this->badges,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
