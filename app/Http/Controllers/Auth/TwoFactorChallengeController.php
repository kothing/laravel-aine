<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TwoFactorChallengeController extends Controller
{
    /**
     * Display the two-factor challenge view.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function create()
    {
        if (! session()->has('login.two_factor_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Verify the submitted TOTP code or recovery code.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $userId = session('login.two_factor_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);

        $code = (string) $request->input('code');
        $recovery = (string) $request->input('recovery_code');

        $valid = false;

        if ($code !== '') {
            $valid = $user->verifyTwoFactorCode($code);
        } elseif ($recovery !== '') {
            $valid = $user->verifyTwoFactorRecoveryCode($recovery);
        }

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => __('The provided two factor authentication code was invalid.'),
            ]);
        }

        Auth::login($user, $request->filled('remember'));

        $request->session()->regenerate();
        $request->session()->forget('login.two_factor_user_id');

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
