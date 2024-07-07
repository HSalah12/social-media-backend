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
            'gender' => 'required|string|max:10',
            'date_of_birth' => 'required|date',
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
            'gender' => $request->gender,
            'date_of_birth' => $request->date_of_birth,
            'password' => Hash::make($request->password),
            
            
            
            // Add other fields as needed
        ]);
            $token = $user->verify_token();
          
        // Send OTP via email
        $this->otpService->sendOTPByEmail($user->email, $otp);

        return response()->json(['message' => 'User registered successfully', 'user' => $user,'otp' => $otp, 'token' => $token], 200);
    }
}
