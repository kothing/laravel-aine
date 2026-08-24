<?php

namespace Aine\Installer\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class canInstall
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return RedirectResponse|mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->alreadyInstalled()) {
            $installedRedirect = config('installer.installedAlreadyAction');

            switch ($installedRedirect) {

                case 'route':
                    $routeName = config('installer.installed.redirectOptions.route.name');
                    $data = config('installer.installed.redirectOptions.route.message');

                    return redirect()->route($routeName)->with(['data' => $data]);

                case 'abort':
                    abort(config('installer.installed.redirectOptions.abort.type'));
                    break;

                case '404':
                case 'default':
                default:
                    abort(404);
                    break;
            }
        }

        return $next($request);
    }

    /**
     * If application is already installed.
     *
     * @return bool
     */
    public function alreadyInstalled()
    {
        return file_exists(storage_path('installed'));
    }
}
