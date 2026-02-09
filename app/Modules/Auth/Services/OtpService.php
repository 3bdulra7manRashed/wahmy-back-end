<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpService
{
    /**
     * OTP expiration time in minutes.
     */
    private const OTP_EXPIRATION_MINUTES = 3;

    /**
     * Maximum number of verification attempts.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Send OTP to the given phone number.
     *
     * @param string $phone
     * @return string The OTP code (for development/testing only when using static OTP)
     */
    public function sendOtp(string $phone): string
    {
        // Delete any previous unverified OTPs for this phone
        OtpCode::where('phone', $phone)
            ->whereNull('verified_at')
            ->delete();

        // Get OTP code
        $staticOtp = config('auth.static_otp');
        $otp = $staticOtp !== null ? (string) $staticOtp : $this->generateRandomOtp();

        // Store hashed OTP
        OtpCode::create([
            'phone' => $phone,
            'code' => Hash::make($otp),
            'expires_at' => now()->addMinutes(self::OTP_EXPIRATION_MINUTES),
            'attempts' => 0,
        ]);

        // In production, you would send the OTP via SMS here
        // For now, we return the OTP (only visible when using static OTP in development)

        return $otp;
    }

    /**
     * Verify OTP for the given phone number.
     *
     * @param string $phone
     * @param string $otp
     * @return bool
     * @throws ValidationException
     */
    public function verifyOtp(string $phone, string $otp): bool
    {
        // Get the latest unverified OTP for this phone
        $otpRecord = OtpCode::where('phone', $phone)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if ($otpRecord === null) {
            throw ValidationException::withMessages([
                'otp' => ['No OTP request found for this phone number.'],
            ]);
        }

        if ($otpRecord->isExpired()) {
            throw ValidationException::withMessages([
                'otp' => ['OTP has expired. Please request a new one.'],
            ]);
        }

        if ($otpRecord->hasExceededAttempts(self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'otp' => ['Maximum verification attempts exceeded. Please request a new OTP.'],
            ]);
        }

        // Verify OTP
        $staticOtp = config('auth.static_otp');
        $isValid = false;

        if ($staticOtp !== null) {
            // Compare directly with static OTP value
            $isValid = $otp === (string) $staticOtp;
        } else {
            // Use hash comparison
            $isValid = Hash::check($otp, $otpRecord->code);
        }

        if (! $isValid) {
            $otpRecord->incrementAttempts();

            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP code.'],
            ]);
        }

        // Mark as verified
        $otpRecord->markAsVerified();

        return true;
    }

    /**
     * Generate a random 4-digit OTP.
     */
    private function generateRandomOtp(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
