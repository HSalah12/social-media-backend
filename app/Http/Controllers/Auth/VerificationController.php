<?php

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
class VerificationController extends Controller
{
    public function verify(Request $request)
    {
        // Retrieve OTP from cache using the email as the key
        $storedOTP = Cache::get('otp');

        // Compare stored OTP with user input
        if ($request->otp == $storedOTP) {
            // OTP is correct, mark email as verified
            
            $user =  $user = Auth::user();
            $user->email_verified_at = now();
            $user->save();

            // Clear OTP from cache
            Cache::forget($request->email);

            // Return a success response
            return response()->json(['message' => 'Email verified successfully'], 200);
        } else {
            // Invalid OTP, return an error response
            return response()->json(['error' => 'Invalid OTP'], 400);
        }
    }
}