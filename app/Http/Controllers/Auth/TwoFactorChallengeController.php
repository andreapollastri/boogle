<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request)
    {
        if (! $request->session()->has('auth.2fa.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:12'],
        ]);

        $userId = $request->session()->get('auth.2fa.user_id');
        $remember = (bool) $request->session()->get('auth.2fa.remember', false);

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget(['auth.2fa.user_id', 'auth.2fa.remember']);

            return redirect()->route('login')->withErrors([
                'code' => 'Two-factor session expired. Please sign in again.',
            ]);
        }

        $normalizedCode = preg_replace('/\s+/', '', $data['code']);
        $isValid = app('pragmarx.google2fa')->verifyKey(decrypt($user->google2fa_secret), $normalizedCode);

        if (! $isValid) {
            return back()->withErrors(['code' => 'Invalid authentication code.'])->withInput();
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->forget(['auth.2fa.user_id', 'auth.2fa.remember']);
        $request->session()->put('auth.2fa.passed', true);

        return redirect()->intended(route('panel.dashboard'));
    }
}
