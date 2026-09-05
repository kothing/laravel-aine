<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apply baseline security headers to every response.
 *
 * The admin area is additionally protected against clickjacking
 * (X-Frame-Options: SAMEORIGIN). The public site intentionally skips the
 * frame header because embedded forms are a core feature: third-party sites
 * load our frontend inside an <iframe>, which any frame restriction would
 * break.
 */
class SecurityHeaders
{
    /**
     * Paths under which the response must not be frameable from other origins.
     */
    private const FRAME_RESTRICTED_PREFIXES = ['admin', 'admin-api'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=()'
        );

        // Production-only: enforce HTTPS via HSTS.
        if (App::isProduction()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Content-Security-Policy: assembled from config('app.csp') so each
        // deployment can customize allowed sources without editing middleware.
        $csp = config('app.csp', []);
        if ($csp) {
            $policy = '';
            foreach ($csp as $directive => $sources) {
                $policy .= $directive . ' ' . implode(' ', $sources) . '; ';
            }
            $response->headers->set('Content-Security-Policy', rtrim($policy));
        }

        if ($this->isFrameRestricted($request->path())) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        return $response;
    }

    /**
     * Whether the request path belongs to the protected admin area.
     */
    private function isFrameRestricted(string $path): bool
    {
        foreach (self::FRAME_RESTRICTED_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }
}
