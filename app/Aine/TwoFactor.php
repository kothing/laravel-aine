<?php

namespace App\Aine;

use Illuminate\Support\Str;

/**
 * Lightweight TOTP (RFC 6238) implementation for two-factor authentication.
 *
 * No external composer dependency required — works with Google Authenticator,
 * Authy, 1Password, etc. via the standard otpauth:// URI.
 */
class TwoFactor
{
    /**
     * Base32 alphabet used by the RFC 4648 encoding.
     */
    public const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a new random base32 secret (default 160 bits = 32 chars).
     */
    public static function generateSecret(int $length = 32): string
    {
        $bytes = random_bytes((int) ceil($length * 5 / 8));

        return static::base32Encode($bytes);
    }

    /**
     * Build the standard otpauth:// provisioning URI.
     */
    public static function provisioningUri(string $secret, string $email, string $issuer = 'Aine CMS'): string
    {
        $label = rawurlencode($issuer.' ('.$email.')');
        $issuerEncoded = rawurlencode($issuer);

        return 'otpauth://totp/'.$label.'?secret='.$secret.'&issuer='.$issuerEncoded.'&algorithm=SHA1&digits=6&period=30';
    }

    /**
     * Verify a 6-digit TOTP code against a secret, allowing a small
     * clock-drift window of ±1 step (configurable).
     */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = trim($code);
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $counter = (int) floor(time() / 30);

        for ($i = -$window; $i <= $window; $i++) {
            $expected = static::codeAt($secret, $counter + $i);

            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compute the TOTP code at a given time counter.
     */
    public static function codeAt(string $secret, int $counter): string
    {
        $key = static::base32Decode($secret);
        $counterBytes = pack('N*', 0, $counter);

        $hash = hash_hmac('sha1', $counterBytes, $key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        $otp = $binary % 1000000;

        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a set of one-time recovery codes.
     */
    public static function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            // Format: XXXX-XXXX-XXXX
            $codes[] = implode('-', [
                strtoupper(Str::random(4)),
                strtoupper(Str::random(4)),
                strtoupper(Str::random(4)),
            ]);
        }

        return $codes;
    }

    /**
     * Verify a recovery code against the stored set. If it matches, the code
     * is consumed (removed from the set) and the remaining codes returned.
     *
     * @return array{valid: bool, remaining_codes: array|null}
     */
    public static function consumeRecoveryCode(array $codes, string $input): array
    {
        $input = strtoupper(trim($input));
        $remaining = [];
        $valid = false;

        foreach ($codes as $code) {
            if (strtoupper(trim($code)) === $input) {
                // Consume this code (skip it) and keep every other code.
                $valid = true;
                continue;
            }
            $remaining[] = $code;
        }

        return ['valid' => $valid, 'remaining_codes' => $valid ? $remaining : null];
    }

    /**
     * Encode a binary string to base32 (RFC 4648, no padding).
     */
    public static function base32Encode(string $data): string
    {
        $result = '';
        $bits = 0;
        $value = 0;

        foreach (str_split($data) as $char) {
            $value = ($value << 8) | ord($char);
            $bits += 8;

            while ($bits >= 5) {
                $result .= static::BASE32_ALPHABET[($value >> ($bits - 5)) & 0x1F];
                $bits -= 5;
            }
        }

        if ($bits > 0) {
            $result .= static::BASE32_ALPHABET[($value << (5 - $bits)) & 0x1F];
        }

        return $result;
    }

    /**
     * Decode a base32 string (RFC 4648) to binary.
     */
    public static function base32Decode(string $data): string
    {
        $data = strtoupper(rtrim($data, "=\n\r\t "));
        $result = '';
        $bits = 0;
        $value = 0;

        foreach (str_split($data) as $char) {
            $index = strpos(static::BASE32_ALPHABET, $char);
            if ($index === false) {
                throw new \InvalidArgumentException('Invalid base32 character: '.$char);
            }

            $value = ($value << 5) | $index;
            $bits += 5;

            if ($bits >= 8) {
                $result .= chr(($value >> ($bits - 8)) & 0xFF);
                $bits -= 8;
            }
        }

        return $result;
    }
}
