@extends('layouts.auth')
@section('title', 'Sign in')

@php($subtitle = 'Sign in to your account')

@section('content')

@if(session('status'))
    <div class="mb-5 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('login') }}" class="space-y-5">
    @csrf

    <x-input name="email" type="email" label="Email address" placeholder="you@example.com" required />

    <div>
        <div class="flex items-center justify-between mb-1.5">
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <a href="{{ route('password.request') }}" class="text-xs text-indigo-600 hover:text-indigo-700">Forgot?</a>
        </div>
        <input id="password" name="password" type="password" placeholder="••••••••" required
               class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-colors @error('password') border-red-400 @enderror" />
        @error('password')
            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-2">
        <input id="remember" name="remember" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
        <label for="remember" class="text-sm text-gray-600">Remember me</label>
    </div>

    <x-btn type="submit" class="w-full justify-center">Sign in</x-btn>
</form>
@endsection
