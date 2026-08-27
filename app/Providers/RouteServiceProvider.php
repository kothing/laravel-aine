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
     * Routes themselves are loaded by bootstrap/app.php via withRouting()
     * (api.php) and the `then` closure (frontend.php / admin.php / auth.php).
     * This provider must NOT re-register the route files — doing so would
     * register every route twice and cause hard-to-diagnose conflicts.
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

        // Write endpoints (create/update/delete/upload) are rate limited more
        // strictly than reads: they are the expensive and destructive paths
        // of the public API. Applies on top of the generic `api` limiter.
        RateLimiter::for('api-write', function (Request $request) {
            return Limit::perMinute(30)->by(optional($request->user())->id ?: $request->ip());
        });

        // Search endpoints run a LIKE "%query%" scan over the content metas,
        // which cannot use an index and gets progressively more expensive as
        // the dataset grows. Keep its own tighter limiter so a single client
        // cannot saturate the database with search requests.
        RateLimiter::for('api-search', function (Request $request) {
            return optional($request->user())->id
                ? Limit::perMinute(60)->by('search:user:'.$request->user()->id)
                : Limit::perMinute(20)->by('search:ip:'.$request->ip());
        });

        // Public (anonymous) form endpoints are exposed to arbitrary visitors,
        // so submissions and uploads are throttled per IP to slow down spam
        // and abuse attempts.
        RateLimiter::for('form-submit', function (Request $request) {
            return Limit::perMinutes(10, 20)->by('form-submit:'.$request->ip());
        });

        RateLimiter::for('form-upload', function (Request $request) {
            return Limit::perMinutes(10, 30)->by('form-upload:'.$request->ip());
        });
    }
}
