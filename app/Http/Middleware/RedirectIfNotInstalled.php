<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirect every request to /install while the application is not installed
 * yet (no storage/installed marker). The installer routes themselves are
 * allowed through, everything else (frontend, admin, API) points at the
 * installer until it completes.
 */
class RedirectIfNotInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->installed()) {
            return $next($request);
        }

        if ($request->is('install') || $request->is('install/*')) {
            return $next($request);
        }

        return redirect('/install');
    }

    /**
     * The application counts as installed once the installer has written the
     * storage/installed marker file (the final step of the wizard).
     */
    protected function installed(): bool
    {
        return file_exists(storage_path('installed'));
    }
}
