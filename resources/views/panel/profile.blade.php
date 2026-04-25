@extends('layouts.app')
@section('title', 'Profile')

@section('content')
<x-page-header title="Profile" description="Manage your account settings" />

<div class="max-w-2xl space-y-6">
    @if(session('plain_token'))
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
            <p class="text-sm font-medium text-amber-900">New API token (shown once):</p>
            <code class="mt-2 block text-xs font-mono bg-white border border-amber-200 px-3 py-2 rounded break-all">{{ session('plain_token') }}</code>
        </div>
    @endif

    {{-- Personal info --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-5">Personal information</h3>
        <form method="POST" action="{{ route('panel.profile.update') }}" class="space-y-5">
            @csrf @method('PATCH')
            <x-input name="name" label="Full name" :value="old('name', $user->name)" required />
            <x-input name="email" type="email" label="Email address" :value="old('email', $user->email)" required />
            <x-btn type="submit">Save changes</x-btn>
        </form>
    </div>

    {{-- Password --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-5">Change password</h3>
        <form method="POST" action="{{ route('panel.profile.password') }}" class="space-y-5">
            @csrf @method('PATCH')
            <x-input name="current_password" type="password" label="Current password" required />
            <x-input name="password" type="password" label="New password" hint="Minimum 8 characters" required />
            <x-input name="password_confirmation" type="password" label="Confirm new password" required />
            <x-btn type="submit">Update password</x-btn>
        </form>
    </div>

    {{-- Notifications --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-5">Notification preferences</h3>
        <form method="POST" action="{{ route('panel.profile.notifications') }}" class="space-y-4">
            @csrf @method('PATCH')
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="notifications[mail]" value="1"
                       {{ ($user->settings['notifications']['mail'] ?? true) ? 'checked' : '' }}
                       class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                <div>
                    <span class="text-sm font-medium text-gray-900">Email notifications</span>
                    <p class="text-xs text-gray-500">Receive digest alerts by email.</p>
                </div>
            </label>

            <div>
                <label for="notifications_snooze_minutes" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Snooze for identical errors (minutes)<span class="text-red-500 ml-0.5">*</span>
                </label>
                <input
                    id="notifications_snooze_minutes"
                    name="notifications[snooze_minutes]"
                    type="number"
                    min="1"
                    max="1440"
                    value="{{ old('notifications.snooze_minutes', $user->settings['notifications']['snooze_minutes'] ?? 15) }}"
                    required
                    class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-colors @error('notifications.snooze_minutes') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                />
                @error('notifications.snooze_minutes')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
                @if(!$errors->has('notifications.snooze_minutes'))
                    <p class="mt-1.5 text-xs text-gray-500">Default 15. You receive one digest every N minutes for repeated identical errors, with the total occurrences.</p>
                @endif
            </div>
            <x-btn type="submit">Save preferences</x-btn>
        </form>
    </div>

    {{-- API Tokens --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-1">API tokens</h3>
        <p class="text-xs text-gray-500 mb-4">Create and revoke your personal API tokens. Tokens are shown only once at creation time.</p>

        <form method="POST" action="{{ route('panel.profile.api-tokens.store') }}" class="space-y-4">
            @csrf
            <x-input name="token_name" label="Token name" placeholder="My local script" required />
            <x-btn type="submit">Create token</x-btn>
        </form>

        <div class="mt-5 space-y-2">
            @forelse($apiTokens as $token)
                <div class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $token->name }}</p>
                        <p class="text-xs text-gray-500">Created {{ $token->created_at?->diffForHumans() ?? 'just now' }}</p>
                    </div>
                    <div x-data="{ openRevokeTokenModal: false }">
                        <button type="button"
                                @click="openRevokeTokenModal = true"
                                class="text-xs px-2.5 py-1.5 rounded border border-red-300 text-red-600 hover:bg-red-50">
                            Revoke
                        </button>

                        <div x-show="openRevokeTokenModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                            <div class="fixed inset-0 bg-black/40" @click="openRevokeTokenModal = false"></div>
                            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 w-9 h-9 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-900">Revoke token “{{ $token->name }}”?</h3>
                                        <p class="mt-2 text-sm text-gray-600">
                                            This action is immediate. Any integration using this token will stop working until a new token is created.
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-6 flex items-center justify-end gap-3">
                                    <x-btn variant="secondary" type="button" @click="openRevokeTokenModal = false">Cancel</x-btn>
                                    <form method="POST" action="{{ route('panel.profile.api-tokens.destroy', $token->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700 transition-colors">
                                            Revoke token
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-xs text-gray-500">No API tokens yet.</p>
            @endforelse
        </div>
    </div>

    {{-- Two factor --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-1">Two-factor authentication</h3>
        <p class="text-xs text-gray-500 mb-4">Protect your account with a time-based one-time password (TOTP).</p>

        @if($user->hasTwoFactorEnabled())
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-green-50 border border-green-200 text-green-700 text-xs font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                Enabled
            </div>
            <form method="POST" action="{{ route('panel.profile.two-factor.disable') }}" class="mt-4"
                  onsubmit="return confirm('Disable two-factor authentication?')">
                @csrf
                @method('DELETE')
                <x-btn type="submit" variant="secondary">Disable 2FA</x-btn>
            </form>
        @else
            @if(empty($pendingTwoFactorSecret))
                <form method="POST" action="{{ route('panel.profile.two-factor.enable') }}">
                    @csrf
                    <x-btn type="submit">Enable 2FA</x-btn>
                </form>
            @else
                <div class="space-y-4">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs text-gray-600 mb-3">Scan this QR code with Google Authenticator, 1Password, Authy, or similar app.</p>
                        <div class="w-44 h-44 rounded-lg border border-gray-200 bg-white p-2 overflow-hidden flex items-center justify-center [&_img]:!w-full [&_img]:!h-full [&_img]:max-w-full [&_img]:max-h-full [&_img]:object-contain [&_img]:block">
                            {!! $twoFactorQrCode !!}
                        </div>
                        <p class="mt-3 text-xs text-gray-600">Manual setup key:</p>
                        <code class="mt-1 inline-block text-xs font-mono bg-white border border-gray-200 px-2 py-1.5 rounded break-all">{{ $pendingTwoFactorSecret }}</code>
                    </div>

                    <form method="POST" action="{{ route('panel.profile.two-factor.confirm') }}" class="space-y-4">
                        @csrf
                        <x-input name="code" label="Authentication code" placeholder="123456" required />
                        <div class="flex items-center gap-3">
                            <x-btn type="submit">Confirm and enable</x-btn>
                            <x-btn type="submit" variant="secondary" name="cancel_setup" value="1" formnovalidate>Cancel setup</x-btn>
                        </div>
                    </form>
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
