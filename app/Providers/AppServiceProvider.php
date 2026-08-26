<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Resources\Json\JsonResource;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        JsonResource::withoutWrapping();

        // ContentSerializer keeps per-request in-memory maps; make sure
        // they never leak into the next request on long-lived processes
        // (Octane, phpunit suites, ...).
        $this->app->terminating(function () {
            \App\Aine\ContentSerializer::reset();
        });
    }
}
