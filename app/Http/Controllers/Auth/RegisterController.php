<?php
// app/Http/Controllers/Auth/RegisterController.php


namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OTPService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\OTPMail;


class RegisterController extends Controller
{
    protected $otpService;

    public function __construct(OTPService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        // Generate OTP
        $otpData = $this->otpService->generateOTP();
        // Generate OTP
        $otp = $otpData['otp'];

        // Create a new user record
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            
            
            // Add other fields as needed
        ]);
            $token = $user->createToken('myToken')->accessToken;
          
        // Send OTP via email
        $this->otpService->sendOTPByEmail($user->email, $otp);

        return response()->json(['message' => 'User registered successfully', 'user' => $user,'otp' => $otp, 'token' => $token], 201);
    }
}
