<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        $pendingSecret = $request->session()->get('auth.2fa.pending_secret');
        $qrCode = null;

        if ($pendingSecret) {
            $qrCode = app('pragmarx.google2fa')->getQRCodeInline(
                config('app.name'),
                $user->email,
                $pendingSecret
            );
        }

        return view('panel.profile', [
            'user' => $user,
            'pendingTwoFactorSecret' => $pendingSecret,
            'twoFactorQrCode' => $qrCode,
            'apiTokens' => $user->tokens()->latest()->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$request->user()->id],
        ]);

        $request->user()->update($data);

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update(['password' => $request->password]);

        return back()->with('success', 'Password updated.');
    }

    public function updateNotifications(Request $request)
    {
        $settings = $request->validate([
            'notifications' => ['array'],
            'notifications.mail' => ['nullable', 'boolean'],
            'notifications.snooze_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        $user = $request->user();
        $user->update([
            'settings' => array_merge($user->settings ?? [], [
                'notifications' => [
                    'mail' => (bool) ($settings['notifications']['mail'] ?? false),
                    'snooze_minutes' => (int) ($settings['notifications']['snooze_minutes'] ?? 15),
                ],
            ]),
        ]);

        return back()->with('success', 'Notification preferences saved.');
    }

    public function enableTwoFactor(Request $request)
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return back()->with('error', 'Two-factor authentication is already enabled.');
        }

        $request->session()->put('auth.2fa.pending_secret', app('pragmarx.google2fa')->generateSecretKey());

        return back()->with('success', 'Scan the QR code and confirm with a one-time code.');
    }

    public function confirmTwoFactor(Request $request)
    {
        if ($request->has('cancel_setup')) {
            $request->session()->forget('auth.2fa.pending_secret');

            return back()->with('success', 'Two-factor setup cancelled.');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:12'],
        ]);

        $secret = $request->session()->get('auth.2fa.pending_secret');

        if (! $secret) {
            return back()->with('error', 'No pending 2FA setup found. Start the setup again.');
        }

        $normalizedCode = preg_replace('/\s+/', '', $data['code']);
        $isValid = app('pragmarx.google2fa')->verifyKey($secret, $normalizedCode);

        if (! $isValid) {
            return back()->withErrors(['code' => 'Invalid authentication code.'])->withInput();
        }

        $request->user()->update([
            'google2fa_secret' => encrypt($secret),
            'google2fa_enabled_at' => now(),
            'google2fa_recovery_codes' => null,
        ]);

        $request->session()->forget('auth.2fa.pending_secret');

        return back()->with('success', 'Two-factor authentication enabled.');
    }

    public function cancelTwoFactorSetup(Request $request)
    {
        $request->session()->forget('auth.2fa.pending_secret');

        return back()->with('success', 'Two-factor setup cancelled.');
    }

    public function disableTwoFactor(Request $request)
    {
        $request->user()->update([
            'google2fa_secret' => null,
            'google2fa_enabled_at' => null,
            'google2fa_recovery_codes' => null,
        ]);

        $request->session()->forget('auth.2fa.pending_secret');
        $request->session()->put('auth.2fa.passed', true);

        return back()->with('success', 'Two-factor authentication disabled.');
    }

    public function createApiToken(Request $request)
    {
        $data = $request->validate([
            'token_name' => ['required', 'string', 'max:255'],
        ]);

        $token = $request->user()->createToken($data['token_name']);

        return back()
            ->with('success', 'API token created.')
            ->with('plain_token', $token->plainTextToken);
    }

    public function revokeApiToken(Request $request, int $tokenId)
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();

        return back()->with('success', 'API token revoked.');
    }
}
