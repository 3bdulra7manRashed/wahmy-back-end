<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Requests\AdminLoginRequest;
use App\Modules\Auth\Requests\SendOtpRequest;
use App\Modules\Auth\Requests\VerifyOtpRequest;
use App\Modules\Auth\Services\OtpService;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService
    ) {}

    /**
     * Send OTP to the given phone number.
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $phone = $request->validated('phone');

        $this->otpService->sendOtp($phone);

        return ApiResponse::success(
            message: 'OTP sent successfully.'
        );
    }

    /**
     * Verify OTP and authenticate user.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $phone = $validated['phone'];
        $otp = $validated['otp'];
        $name = $validated['name'] ?? null;

        // Verify OTP
        $this->otpService->verifyOtp($phone, $otp);

        // Find or create user
        $user = User::where('phone', $phone)->first();

        if ($user === null) {
            // User doesn't exist - require name
            if (empty($name)) {
                throw ValidationException::withMessages([
                    'name' => ['Name is required for new users.'],
                ]);
            }

            // Create new customer user
            $user = User::create([
                'name' => $name,
                'phone' => $phone,
                'role' => UserRole::CUSTOMER,
                'phone_verified_at' => now(),
                'is_active' => true,
            ]);
        } else {
            // Update phone_verified_at if not already set
            if ($user->phone_verified_at === null) {
                $user->update(['phone_verified_at' => now()]);
            }
        }

        // Issue JWT token
        $token = auth('api')->login($user);

        return ApiResponse::success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function me(): JsonResponse
    {
        return ApiResponse::success(auth()->user());
    }

    /**
     * Logout the user (invalidate the token).
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return ApiResponse::success(
            message: 'Successfully logged out.'
        );
    }

    /**
     * Refresh the JWT token.
     */
    public function refresh(): JsonResponse
    {
        $token = auth('api')->refresh();

        return ApiResponse::success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }

    /**
     * Admin login using email and password.
     */
    public function adminLogin(AdminLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];
        $password = $validated['password'];

        // Find user by email
        $user = User::where('email', $email)->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        // Check if user is an admin
        if ($user->role !== UserRole::ADMIN) {
            return ApiResponse::error(
                'Access denied. Admin privileges required.',
                403
            );
        }

        // Check if user is active
        if (! $user->is_active) {
            return ApiResponse::error(
                'Account is deactivated. Please contact support.',
                403
            );
        }

        // Verify password
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        // Issue JWT token
        $token = auth('api')->login($user);

        return ApiResponse::success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }
}
