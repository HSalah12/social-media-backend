<?php

namespace App\Http\Controllers\Auth;

use App\Models\UserStatus;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Events\UserActionOccurred;



class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        $user = Auth::user();
    
        if ($user) {
            // Update user status to offline
            UserStatus::updateOrCreate(
                ['user_id' => $user->id],
                ['status' => 'offline', 'last_seen_at' => now()]
            );
    
            // Revoke all tokens for the user
            $user->tokens->each(function ($token) {
                $token->delete();
            });
        }
        event(new UserActionOccurred('User logged out', auth()->id()));

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}    
        



