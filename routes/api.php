<?php

declare(strict_types=1);

use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Branch\Controllers\BranchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
*/
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

/*
|--------------------------------------------------------------------------
| API Version 1
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {

    /*
    |----------------------------------------------------------------------
    | Authentication Routes
    |----------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {

        // Public OTP auth routes
        Route::post('/send-otp', [AuthController::class, 'sendOtp'])
            ->middleware('throttle:5,1');

        Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
            ->middleware('throttle:10,1');

        // Protected auth routes
        Route::middleware('auth:api')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
        });

    });

    /*
    |----------------------------------------------------------------------
    | Public Routes
    |----------------------------------------------------------------------
    */
    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/branches/{branch}', [BranchController::class, 'show']);

    /*
    |----------------------------------------------------------------------
    | Admin Routes
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->group(function () {

        // Admin login (public)
        Route::post('/login', [AuthController::class, 'adminLogin'])
            ->middleware('throttle:5,1');

        // Protected admin routes
        Route::middleware(['auth:api', 'admin'])->group(function () {

            Route::get('/me', function () {
                return response()->json([
                    'success' => true,
                    'data' => auth()->user(),
                ]);
            });

        });

    });

    /*
    |----------------------------------------------------------------------
    | Protected Routes (Requires JWT Authentication)
    |----------------------------------------------------------------------
    */
    Route::middleware('auth:api')->group(function () {

        // JWT Test Route (temporary for verification)
        Route::get('/jwt-test', function () {
            return response()->json([
                'message' => 'JWT working',
                'user' => auth()->user(),
            ]);
        });

    });

});
