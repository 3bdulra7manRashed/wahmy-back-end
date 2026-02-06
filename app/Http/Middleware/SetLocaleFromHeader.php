<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromHeader
{
    /**
     * Supported locales.
     *
     * @var array<string>
     */
    private const SUPPORTED_LOCALES = ['ar', 'en'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->extractLocale($request);

        app()->setLocale($locale);

        return $next($request);
    }

    /**
     * Extract the primary language from Accept-Language header.
     */
    private function extractLocale(Request $request): string
    {
        $acceptLanguage = $request->header('Accept-Language');

        if ($acceptLanguage === null) {
            return config('app.locale', 'ar');
        }

        // Extract primary language code (e.g., "ar-SA" → "ar", "en-US" → "en")
        $primaryLanguage = strtolower(substr($acceptLanguage, 0, 2));

        if (in_array($primaryLanguage, self::SUPPORTED_LOCALES, true)) {
            return $primaryLanguage;
        }

        return config('app.locale', 'ar');
    }
}
