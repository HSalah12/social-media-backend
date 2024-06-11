<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Hash;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $user = User::where('email', $credentials['email'])->first();
        
        if ($user && Hash::check($credentials['password'], $user->password)) {
            if (!$user->hasVerifiedEmail()) {
                return response()->json([
                    'error' => 'Email Not Verified',
                    'message' => 'Please verify your email address before logging in.'
                ], 403);
            }

            $token = $user->createToken('myToken')->accessToken;
            $user->update(['active' => true]);
            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token,
            ], 200);
        }

        return response()->json([
            'error' => 'Unauthorized',
            'message' => 'Email or Password incorrect'
        ], 401);
    }
}

