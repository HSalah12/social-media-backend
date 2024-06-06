<?php

// app/Http/Controllers/Auth/ResetPasswordController.php
namespace App\Http\Controllers\Auth;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use App\Services\OTPService;
use Auth;

class ResetPasswordController extends Controller
{
    public function reset(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Retrieve the authenticated user
        $user = Auth::user();

        // Update the user's password
        $user->password = \Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password reset successfully']);
    }
}
