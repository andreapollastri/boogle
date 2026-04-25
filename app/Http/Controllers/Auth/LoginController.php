<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = $request->user();

            if ($user?->hasTwoFactorEnabled()) {
                Auth::logout();
                $request->session()->put('auth.2fa.user_id', $user->id);
                $request->session()->put('auth.2fa.remember', $request->boolean('remember'));

                return redirect()->route('two-factor.challenge');
            }

            $request->session()->regenerate();
            $request->session()->put('auth.2fa.passed', true);

            return redirect()->intended(route('panel.dashboard'));
        }

        return back()->withErrors(['email' => 'These credentials do not match our records.'])->withInput();
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
