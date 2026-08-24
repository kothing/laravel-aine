<?php

namespace Aine\Installer\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Aine\Installer\Middleware\canInstall;
use Aine\Installer\Middleware\canUpdate;

class InstallerServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
    }

    /**
     * Bootstrap the application events.
     *
     * @param \Illuminate\Routing\Router $router
     */
    public function boot(Router $router)
    {
        $router->middlewareGroup('install', [CanInstall::class]);
        $router->middlewareGroup('update', [CanUpdate::class]);
    }

}
