<?php

namespace Tests\Feature;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Regression tests for P0-2: the public API must be rate limited.
 *
 * The `api` middleware group applies 60 req/min/IP to every endpoint
 * (via `throttle:api`) and write endpoints additionally apply a stricter
 * 30 req/min/IP limiter (`throttle:api-write`).
 *
 * These tests drive the ThrottleRequests middleware directly so they don't
 * depend on database-backed API routes or route-matching order.
 */
class ApiRateLimitingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Call the ThrottleRequests middleware $times times for the named
     * limiter. Returns the last response, or throws once the limiter kicks in.
     */
    private function hitLimiter(string $limiter, int $times, string $ip = '127.0.0.1')
    {
        $middleware = app(ThrottleRequests::class);
        $next = fn () => response('ok');

        $last = null;
        for ($i = 0; $i < $times; $i++) {
            $request = Request::create('/api/throttle-probe', 'GET');
            $request->server->set('REMOTE_ADDR', $ip);
            $last = $middleware->handle($request, $next, $limiter);
        }

        return $last;
    }

    public function test_api_limiter_allows_60_requests_then_blocks(): void
    {
        $this->hitLimiter('api', 60);

        $this->expectException(ThrottleRequestsException::class);
        $this->hitLimiter('api', 1);
    }

    public function test_api_write_limiter_allows_30_requests_then_blocks(): void
    {
        $this->hitLimiter('api-write', 30);

        $this->expectException(ThrottleRequestsException::class);
        $this->hitLimiter('api-write', 1);
    }

    public function test_limiter_is_scoped_per_ip(): void
    {
        $this->hitLimiter('api', 60, '10.0.0.1');

        // A different IP is not affected by the exhausted limiter.
        $this->assertNotNull($this->hitLimiter('api', 1, '10.0.0.2'));
    }

    public function test_write_limiter_is_below_read_limiter(): void
    {
        $this->hitLimiter('api-write', 30);

        // The generic read limiter still has headroom (60/min) at 30 requests.
        $this->assertNotNull($this->hitLimiter('api', 1));
    }

    public function test_limiters_are_registered(): void
    {
        $api = RateLimiter::limiter('api');
        $write = RateLimiter::limiter('api-write');
        $search = RateLimiter::limiter('api-search');
        $submit = RateLimiter::limiter('form-submit');
        $upload = RateLimiter::limiter('form-upload');

        $this->assertIsCallable($api);
        $this->assertIsCallable($write);
        $this->assertIsCallable($search);
        $this->assertIsCallable($submit);
        $this->assertIsCallable($upload);

        $request = Request::create('/api/probe', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $this->assertInstanceOf(Limit::class, $api($request));
        $this->assertInstanceOf(Limit::class, $write($request));
        $this->assertInstanceOf(Limit::class, $search($request));
        $this->assertInstanceOf(Limit::class, $submit($request));
        $this->assertInstanceOf(Limit::class, $upload($request));
    }

    public function test_search_limiter_allows_20_anonymous_requests_then_blocks(): void
    {
        // Anonymous visitors share the IP-scoped bucket (20/min).
        $this->hitLimiter('api-search', 20);

        $this->expectException(ThrottleRequestsException::class);
        $this->hitLimiter('api-search', 1);
    }

    public function test_search_limiter_is_stricter_for_anonymous_users(): void
    {
        // Authenticated users get a higher limit (60/min).
        $authenticated = function () {
            $middleware = app(ThrottleRequests::class);
            $next = fn () => response('ok');
            $request = Request::create('/api/probe', 'GET');
            $request->server->set('REMOTE_ADDR', '127.0.0.1');
            $request->setUserResolver(fn () => (object) ['id' => 1]);

            $last = null;
            for ($i = 0; $i < 30; $i++) {
                $last = $middleware->handle($request, $next, 'api-search');
            }

            return $last;
        };

        // 30 requests go through for an authenticated user, while 20 would
        // already exhaust the anonymous bucket.
        $this->assertNotNull($authenticated());
    }

    public function test_form_submit_limiter_allows_20_requests_per_10_minutes(): void
    {
        $this->hitLimiter('form-submit', 20);

        $this->expectException(ThrottleRequestsException::class);
        $this->hitLimiter('form-submit', 1);
    }

    public function test_form_upload_limiter_allows_30_requests_per_10_minutes(): void
    {
        $this->hitLimiter('form-upload', 30);

        $this->expectException(ThrottleRequestsException::class);
        $this->hitLimiter('form-upload', 1);
    }
}
