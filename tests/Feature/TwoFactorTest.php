<?php

namespace Tests\Feature;

use App\Aine\TwoFactor;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Tests for F6-1: two-factor authentication (RFC 6238 TOTP).
 */
class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Admin',
            'email' => 'admin@2fa.test',
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * RFC 6238 Appendix B test vectors (SHA1, 8-digit in the RFC; our 6-digit
     * codes are the last 6 digits). The counter passed to TOTP is T = floor(Time/30).
     */
    public static function rfcVectors(): array
    {
        // secret "12345678901234567890" in base32
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        return [
            // Time 59 -> T 1
            [$secret, 1, '287082'],
            // Time 1111111109 -> T 37037036
            [$secret, 37037036, '081804'],
            // Time 1111111111 -> T 37037037
            [$secret, 37037037, '050471'],
            // Time 1234567890 -> T 41152263
            [$secret, 41152263, '005924'],
            // Time 2000000000 -> T 66666666
            [$secret, 66666666, '279037'],
            // Time 20000000000 -> T 666666666
            [$secret, 666666666, '353130'],
        ];
    }

    #[DataProvider('rfcVectors')]
    public function test_totp_matches_rfc_6238_vectors(string $secret, int $counter, string $expected): void
    {
        $this->assertSame($expected, TwoFactor::codeAt($secret, $counter));
    }

    public function test_base32_round_trip(): void
    {
        $secret = TwoFactor::generateSecret();

        $this->assertSame(32, strlen($secret));
        $this->assertSame(
            $secret,
            TwoFactor::base32Encode(TwoFactor::base32Decode($secret))
        );
    }

    public function test_verify_accepts_valid_code_and_rejects_invalid(): void
    {
        $secret = TwoFactor::generateSecret();
        $code = TwoFactor::codeAt($secret, (int) floor(time() / 30));

        $this->assertTrue(TwoFactor::verify($secret, $code));
        $this->assertFalse(TwoFactor::verify($secret, '000000'));
        $this->assertFalse(TwoFactor::verify($secret, ''));
        $this->assertFalse(TwoFactor::verify($secret, 'abc'));
    }

    public function test_recovery_codes_generate_and_consume(): void
    {
        $codes = TwoFactor::generateRecoveryCodes(3);
        $this->assertCount(3, $codes);

        $result = TwoFactor::consumeRecoveryCode($codes, $codes[1]);

        $this->assertTrue($result['valid']);
        $this->assertCount(2, $result['remaining_codes']);
        $this->assertNotContains($codes[1], $result['remaining_codes']);
    }

    public function test_enable_endpoint_returns_secret(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/admin-api/user/2fa/enable');

        $response->assertStatus(200);
        $response->assertJsonStructure(['secret', 'provisioning_uri']);
        $this->assertStringContainsString('otpauth://totp/', $response->json('provisioning_uri'));
    }

    public function test_confirm_endpoint_requires_valid_code(): void
    {
        $this->actingAs($this->user);

        $enable = $this->postJson('/admin-api/user/2fa/enable');
        $secret = $enable->json('secret');

        // Invalid code
        $this->postJson('/admin-api/user/2fa/confirm', ['code' => '000000'])->assertStatus(422);

        // Valid code (computed against the same secret)
        $code = TwoFactor::codeAt($secret, (int) floor(time() / 30));
        $response = $this->postJson('/admin-api/user/2fa/confirm', ['code' => $code]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['recovery_codes']);

        $this->user->refresh();
        $this->assertTrue($this->user->twoFactorEnabled());
    }

    public function test_disable_endpoint_requires_password(): void
    {
        $this->actingAs($this->user);

        $enable = $this->postJson('/admin-api/user/2fa/enable');
        $secret = $enable->json('secret');
        $code = TwoFactor::codeAt($secret, (int) floor(time() / 30));
        $this->postJson('/admin-api/user/2fa/confirm', ['code' => $code])->assertStatus(200);

        // Wrong password
        $this->postJson('/admin-api/user/2fa/disable', ['password' => 'wrong'])->assertStatus(422);

        // Correct password
        $response = $this->postJson('/admin-api/user/2fa/disable', ['password' => 'password']);

        $response->assertStatus(200);
        $this->user->refresh();
        $this->assertFalse($this->user->twoFactorEnabled());
    }

    public function test_login_without_2fa_redirects_home(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@2fa.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(RouteServiceProvider::HOME);
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_login_with_2fa_redirects_to_challenge(): void
    {
        // Enable 2FA directly on the model
        $this->enableTwoFactorDirectly();

        $response = $this->post('/login', [
            'email' => 'admin@2fa.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
    }

    public function test_challenge_accepts_valid_totp_code(): void
    {
        $this->enableTwoFactorDirectly();

        $this->post('/login', [
            'email' => 'admin@2fa.test',
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        $secret = $this->user->two_factor_secret;
        $code = TwoFactor::codeAt($secret, (int) floor(time() / 30));

        $response = $this->post('/two-factor-challenge', ['code' => $code]);

        $response->assertRedirect(RouteServiceProvider::HOME);
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_challenge_rejects_invalid_code(): void
    {
        $this->enableTwoFactorDirectly();

        $this->post('/login', [
            'email' => 'admin@2fa.test',
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->post('/two-factor-challenge', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_challenge_accepts_recovery_code(): void
    {
        $this->enableTwoFactorDirectly();

        $this->post('/login', [
            'email' => 'admin@2fa.test',
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        $codes = $this->user->twoFactorRecoveryCodes();
        $code = $codes[0];

        $response = $this->post('/two-factor-challenge', ['recovery_code' => $code]);

        $response->assertRedirect(RouteServiceProvider::HOME);
        $this->assertAuthenticatedAs($this->user);

        // Recovery code is consumed
        $this->user->refresh();
        $this->assertNotContains($code, $this->user->twoFactorRecoveryCodes());
    }

    private function enableTwoFactorDirectly(): void
    {
        $secret = TwoFactor::generateSecret();
        $this->user->two_factor_secret = $secret;
        $this->user->setTwoFactorRecoveryCodes(TwoFactor::generateRecoveryCodes());
        $this->user->two_factor_confirmed_at = now();
        $this->user->save();
    }
}
