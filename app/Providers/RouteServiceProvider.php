<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/admin';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('api-write', function (Request $request) {
            return Limit::perMinute(30)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('api-search', function (Request $request) {
            return optional($request->user())->id
                ? Limit::perMinute(60)->by('search:user:'.$request->user()->id)
                : Limit::perMinute(20)->by('search:ip:'.$request->ip());
        });

        RateLimiter::for('form-submit', function (Request $request) {
            return Limit::perMinutes(10, 20)->by('form-submit:'.$request->ip());
        });

        RateLimiter::for('form-upload', function (Request $request) {
            return Limit::perMinutes(10, 30)->by('form-upload:'.$request->ip());
        });

        RateLimiter::for('preview', function (Request $request) {
            return Limit::perMinute(60)->by('preview:'.$request->ip());
        });
    }
}
