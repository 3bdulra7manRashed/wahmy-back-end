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
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Customer Auth", description: "Customer OTP-based authentication")]
#[OA\Tag(name: "Admin Auth", description: "Admin email/password authentication")]
#[OA\Tag(name: "Auth", description: "Shared authentication endpoints")]
class AuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService
    ) {}

    /**
     * Send OTP to the given phone number.
     */
    #[OA\Post(
        path: "/auth/send-otp",
        summary: "Send OTP to phone number",
        description: "Sends a one-time password (OTP) to the provided phone number for customer authentication.",
        operationId: "sendOtp",
        tags: ["Customer Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Phone number to send OTP to",
            content: new OA\JsonContent(
                required: ["phone"],
                properties: [
                    new OA\Property(property: "phone", type: "string", example: "0500000000", description: "Customer phone number")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OTP sent successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "OTP sent successfully.")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse")
            )
        ]
    )]
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
    #[OA\Post(
        path: "/auth/verify-otp",
        summary: "Verify OTP and get JWT token",
        description: "Verifies the OTP code and returns a JWT token. Creates a new customer account if the phone number is not registered.",
        operationId: "verifyOtp",
        tags: ["Customer Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "OTP verification data",
            content: new OA\JsonContent(
                required: ["phone", "otp"],
                properties: [
                    new OA\Property(property: "phone", type: "string", example: "0500000000", description: "Customer phone number"),
                    new OA\Property(property: "otp", type: "string", example: "1234", description: "OTP code received via SMS"),
                    new OA\Property(property: "name", type: "string", example: "Ahmed", description: "Customer name (required for new users)")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Successfully authenticated",
                content: new OA\JsonContent(ref: "#/components/schemas/TokenResponse")
            ),
            new OA\Response(
                response: 422,
                description: "Validation error or invalid OTP",
                content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse")
            )
        ]
    )]
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
    #[OA\Get(
        path: "/auth/me",
        summary: "Get authenticated user",
        description: "Returns the currently authenticated user's profile information.",
        operationId: "getAuthenticatedUser",
        tags: ["Auth"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "User profile retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/UserResponse")
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function me(): JsonResponse
    {
        return ApiResponse::success(auth()->user());
    }

    /**
     * Logout the user (invalidate the token).
     */
    #[OA\Post(
        path: "/auth/logout",
        summary: "Logout user",
        description: "Invalidates the current JWT token (blacklists it).",
        operationId: "logout",
        tags: ["Auth"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successfully logged out",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Successfully logged out.")
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
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
    #[OA\Post(
        path: "/auth/refresh",
        summary: "Refresh JWT token",
        description: "Refreshes the current JWT token and returns a new one.",
        operationId: "refreshToken",
        tags: ["Auth"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Token refreshed successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/TokenResponse")
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated or token cannot be refreshed",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
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
    #[OA\Post(
        path: "/admin/login",
        summary: "Admin login",
        description: "Authenticates an admin user using email and password. Returns a JWT token on success.",
        operationId: "adminLogin",
        tags: ["Admin Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Admin credentials",
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "admin@branch.test", description: "Admin email address"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123", description: "Admin password")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Successfully authenticated",
                content: new OA\JsonContent(ref: "#/components/schemas/TokenResponse")
            ),
            new OA\Response(
                response: 403,
                description: "Access denied or account deactivated",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 422,
                description: "Invalid credentials",
                content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse")
            )
        ]
    )]
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
