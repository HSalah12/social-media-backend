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
        $user = User::where('email',$credentials['email'])->first();
        $password = Hash::check($credentials['password'],$user->password);
        if ($password) {
            $token = $user->createToken('myToken')->accessToken;
            return response()->json(['message' => 'login successfully', 'user' => $user,'token' => $token,], 201);
        }

        return response()->json([
            'error' => 'Unauthorized',
            'token' => $token,
            'message' => 'not autherized'
        ], 401);
    }

//     public function logout(Request $request)
// {
//     Auth::logout();
//     return response()->json(['message' => 'User logged out successfully']);
// }
}
