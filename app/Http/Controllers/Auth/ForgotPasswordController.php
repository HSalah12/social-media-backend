<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Support\Str;
use App\Services\OTPService;
use Carbon\Carbon;
use App\Mail\OTPMail;
use Auth;


class ForgotPasswordController extends Controller
{

    protected $otpService; // Define $otpService property

    public function __construct(OTPService $otpService)
    {
        $this->otpService = $otpService; // Initialize $otpService in constructor
    }
    public function forgot(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email not found'], 404);
        }

        // Generate reset token
        $token = $user->verify_token();
        // Store reset token and timestamp in database

        $otpData = $this->otpService->generateOTP();
        // Generate OTP
        $otp = $otpData['otp'];

        return response()->json(['message' => 'Reset password email sent','token' => $token, 'otp' => $otp]);
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email not found'], 404);
        }

        // Generate OTP
        $otpData = $this->otpService->generateOTP();
        $otp = $otpData['otp'];
        $token = $user->verify_token();
        // Send OTP to the user's email
        Mail::to($user->email)->send(new OTPMail($otp));

        return response()->json(['message' => 'OTP resent successfully','token' => $token, 'otp' => $otp]);
    }

}
