<?php

// app/Services/OTPService.php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

use App\Mail\OTPMail;
use Carbon\Carbon;

class OTPService
{
    public function generateOTP()
    {
        $otp = rand(100000, 999999); // Generate a random 6-digit OTP
        Cache::put('otp', $otp, now()->addMinutes(2));
        $expiry = Carbon::now()->addMinutes(5); // OTP expires in 5 minutes
        return ['otp' => $otp, 'expiry' => $expiry];
    }

    public function validateOTP($otp, $expiry)
    {
        // Check if current time is before expiry
        if (Carbon::now()->lt($expiry)) {
            return true; // OTP is valid
        } else {
            return false; // OTP has expired
        }
    }

    public function sendOTPByEmail($email, $otp)
    {
        // Logic to send OTP via email
        Mail::to($email)->send(new OTPMail($otp));
    }
}
