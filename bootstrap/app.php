<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\SetLocaleFromHeader::class,
        ]);

        // Register middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        // CRITICAL: Prevent redirect for API routes (API-only JWT project)
        // Returns null for API routes which triggers AuthenticationException instead of redirect
        $middleware->redirectGuestsTo(fn () => null);

    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // CRITICAL: Force JSON rendering for all API requests
        // This ensures exceptions are rendered as JSON, not HTML/redirect
        $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $e) => 
            $request->is('api/*') || $request->expectsJson()
        );

        // Handle ValidationException
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // Handle JWT Token Exceptions
        $exceptions->render(function (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired.',
            ], 401);
        });

        $exceptions->render(function (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Token is invalid.',
            ], 401);
        });

        $exceptions->render(function (\Tymon\JWTAuth\Exceptions\TokenBlacklistedException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Token has been blacklisted.',
            ], 401);
        });

        $exceptions->render(function (\Tymon\JWTAuth\Exceptions\JWTException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Token error: ' . $e->getMessage(),
            ], 401);
        });

        // Handle AuthenticationException (always return JSON for API)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        });

        // Handle UnauthorizedHttpException (Symfony 401)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        });

        // Handle ModelNotFoundException
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                ], 404);
            }
        });

        // Handle NotFoundHttpException (for route model binding failures)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                ], 404);
            }
        });

        // Handle all other exceptions for API requests
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $response = [
                    'success' => false,
                    'message' => 'Server error.',
                ];

                // Show debug info only in debug mode
                if (config('app.debug')) {
                    $response['debug'] = [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ];
                }

                return response()->json($response, 500);
            }
        });

    })->create();
