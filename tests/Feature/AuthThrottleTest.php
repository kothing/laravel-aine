<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for P1-1: rate limiting on authentication write
 * endpoints. These limits protect against password reset email bombing and
 * brute forcing of the 2FA challenge / password reset codes.
 */
class AuthThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_blocks_after_6_requests_per_minute(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/forgot-password', ['email' => 'nobody@example.com']);
            $this->assertTrue(
                in_array($response->getStatusCode(), [200, 302, 419, 422], true),
                'Requests inside the limit should not be rejected.'
            );
        }

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertStatus(429);
    }

    public function test_two_factor_challenge_blocks_after_5_requests_per_minute(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/two-factor-challenge', ['code' => '123456']);
            $this->assertTrue(
                in_array($response->getStatusCode(), [200, 302, 419, 422], true),
                'Requests inside the limit should not be rejected.'
            );
        }

        $this->post('/two-factor-challenge', ['code' => '123456'])
            ->assertStatus(429);
    }

    public function test_reset_password_blocks_after_5_requests_per_minute(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/reset-password', [
                'token' => 'token',
                'email' => 'nobody@example.com',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);
            $this->assertTrue(
                in_array($response->getStatusCode(), [200, 302, 419, 422], true),
                'Requests inside the limit should not be rejected.'
            );
        }

        $this->post('/reset-password', ['token' => 'token', 'email' => 'nobody@example.com'])
            ->assertStatus(429);
    }
}
