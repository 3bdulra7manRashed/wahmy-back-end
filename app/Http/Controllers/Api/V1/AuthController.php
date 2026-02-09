<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\UserResource;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Authenticate user and return access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password']);

        if (!Auth::attempt($credentials)) {
            return ApiResponse::error('Invalid credentials.', 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(message: 'Successfully logged out.');
    }

    /**
     * Get the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'user' => new UserResource($request->user()),
        ]);
    }
}
