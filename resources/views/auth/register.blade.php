@extends('layouts.auth')
@section('title', 'Create account')
@php($subtitle = 'Start tracking exceptions for free')

@section('content')
<form method="POST" action="{{ route('register') }}" class="space-y-5">
    @csrf
    <x-input name="name" label="Full name" placeholder="Jane Doe" required />
    <x-input name="email" type="email" label="Email address" placeholder="you@example.com" required />
    <x-input name="password" type="password" label="Password" placeholder="Min. 8 characters" required />
    <x-input name="password_confirmation" type="password" label="Confirm password" placeholder="••••••••" required />
    <x-btn type="submit" class="w-full justify-center">Create account</x-btn>
</form>
<p class="mt-6 text-center text-sm text-gray-500">
    Already have an account? <a href="{{ route('login') }}" class="text-indigo-600 font-medium hover:text-indigo-700">Sign in</a>
</p>
@endsection
