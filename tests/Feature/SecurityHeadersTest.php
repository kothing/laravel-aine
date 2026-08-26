<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Regression tests for the global SecurityHeaders middleware (P1-2).
 *
 * The public site must stay embeddable (embedded forms are loaded inside
 * third-party iframes), so it gets baseline headers but no frame
 * restriction. The admin area is protected against clickjacking.
 */
class SecurityHeadersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The RedirectIfNotInstalled middleware redirects everything while
        // the installer marker is missing; make the app appear installed so
        // the header behavior itself is what gets exercised.
        if (! file_exists(storage_path('installed'))) {
            file_put_contents(storage_path('installed'), '1');
        }
    }

    public function test_public_pages_are_embeddable_but_have_baseline_headers(): void
    {
        Route::get('/_header-probe', fn () => response('ok'));

        $response = $this->get('/_header-probe');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->assertHeaderMissing('X-Frame-Options');
    }

    public function test_admin_pages_are_frame_restricted(): void
    {
        // Any /admin/* path is protected against framing. Guests are
        // redirected to the login page, and the middleware applies the
        // headers to the redirect response as well.
        $response = $this->get('/admin/login');

        $response->assertRedirect('/login');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
