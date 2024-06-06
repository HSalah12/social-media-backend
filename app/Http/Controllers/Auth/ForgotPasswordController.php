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
    $resetToken = base64_encode(random_bytes(32));

        // Store reset token and timestamp in database
        DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email],
            ['token' => $resetToken, 'created_at' => Carbon::now()]
        );
        $otpData = $this->otpService->generateOTP();
        // Generate OTP
        $otp = $otpData['otp'];

        // Send reset email to user
        Mail::to($user->email)->send(new ResetPasswordMail($resetToken));

        return response()->json(['message' => 'Reset password email sent','token' => $resetToken, 'otp' => $otp]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8',
        ]);
    
        $resetToken = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();
    
        if (!$resetToken) {
            return response()->json(['message' => 'Invalid reset token'], 400);
        }
    
        // Check if reset token is expired (optional)
        $expirationTime = Carbon::parse($resetToken->created_at)->addMinutes(60); // Adjust as needed
        if (Carbon::now()->gt($expirationTime)) {
            return response()->json(['message' => 'Reset token expired'], 400);
        }
    
        // Update user's password
        $user = User::where('email', $request->email)->first();
        $user->password = bcrypt($request->password);
        $user->save();
    
        // Delete reset token from database
        DB::table('password_resets')->where('email', $request->email)->delete();
    
        return response()->json(['message' => 'Password reset successfully']);
    }
    
}
