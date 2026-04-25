<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorChallengePassed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return $next($request);
        }

        if ($request->session()->get('auth.2fa.passed', false) === true) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->put('auth.2fa.user_id', $user->id);
        $request->session()->put('auth.2fa.remember', false);

        return redirect()->route('two-factor.challenge');
    }
}
