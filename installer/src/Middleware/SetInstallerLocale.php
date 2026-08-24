<?php

namespace Aine\Installer\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Installer UI language.
 *
 * The user picks a language on the welcome page (?locale=en|zh_CN); the
 * choice is remembered in the session for the rest of the wizard. Until a
 * choice is made, English is the default.
 */
class SetInstallerLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('installer_locale', 'en');

        $requested = $request->query('locale');
        if (in_array($requested, array_keys(config('installer.locales', ['en' => 'English'])), true)) {
            $locale = $requested;
            session(['installer_locale' => $locale]);
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
