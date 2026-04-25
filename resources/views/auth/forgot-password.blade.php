@extends('layouts.auth')
@section('title', 'Reset password')
@php($subtitle = 'Send yourself a reset link')

@section('content')
@if(session('status'))
    <div class="mb-5 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">{{ session('status') }}</div>
@endif
<form method="POST" action="{{ route('password.email') }}" class="space-y-5">
    @csrf
    <x-input name="email" type="email" label="Email address" placeholder="you@example.com" required />
    <x-btn type="submit" class="w-full justify-center">Send reset link</x-btn>
</form>
<p class="mt-6 text-center text-sm text-gray-500">
    <a href="{{ route('login') }}" class="text-indigo-600 font-medium hover:text-indigo-700">← Back to sign in</a>
</p>
@endsection
