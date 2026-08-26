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

        $this->assertIsCallable($api);
        $this->assertIsCallable($write);

        $request = Request::create('/api/probe', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $this->assertInstanceOf(Limit::class, $api($request));
        $this->assertInstanceOf(Limit::class, $write($request));
    }
}
