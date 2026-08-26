<?php

namespace App\Http\Controllers\Auth;

use App\Aine\TwoFactor;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    /**
     * Generate (or reuse) a two-factor secret for the current user and return
     * the provisioning data needed to set up the authenticator app.
     */
    public function enable(Request $request)
    {
        $user = $request->user();

        if ($user->twoFactorEnabled()) {
            return response()->json(['message' => 'Two factor authentication is already enabled.'], 422);
        }

        if (! $user->two_factor_secret) {
            $user->two_factor_secret = TwoFactor::generateSecret();
            $user->save();
        }

        return response()->json([
            'secret' => $user->two_factor_secret,
            'provisioning_uri' => TwoFactor::provisioningUri($user->two_factor_secret, $user->email),
        ], 200);
    }

    /**
     * Verify a code from the authenticator app and fully enable 2FA, returning
     * the one-time recovery codes.
     */
    public function confirm(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            return response()->json(['message' => 'Enable two factor authentication first.'], 422);
        }

        if (! TwoFactor::verify($user->two_factor_secret, $request->input('code'))) {
            return response()->json(['message' => 'The provided code was invalid.'], 422);
        }

        $codes = TwoFactor::generateRecoveryCodes();
        $user->setTwoFactorRecoveryCodes($codes);
        $user->two_factor_confirmed_at = now();
        $user->save();

        return response()->json(['recovery_codes' => $codes], 200);
    }

    /**
     * Disable two-factor authentication after confirming the current password.
     */
    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $user = $request->user();

        if (! Auth::validate(['email' => $user->email, 'password' => $request->input('password')])) {
            throw ValidationException::withMessages([
                'password' => __('The provided password is incorrect.'),
            ]);
        }

        $user->disableTwoFactor();

        return response()->json(['message' => 'Two factor authentication has been disabled.'], 200);
    }

    /**
     * Regenerate the one-time recovery codes (invalidates the previous set).
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $user = $request->user();

        if (! $user->twoFactorEnabled()) {
            return response()->json(['message' => 'Two factor authentication is not enabled.'], 422);
        }

        $codes = TwoFactor::generateRecoveryCodes();
        $user->setTwoFactorRecoveryCodes($codes);
        $user->save();

        return response()->json(['recovery_codes' => $codes], 200);
    }
}
