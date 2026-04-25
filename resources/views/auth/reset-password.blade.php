@extends('layouts.auth')
@section('title', 'New password')
@php($subtitle = 'Create a new password')

@section('content')
<form method="POST" action="{{ route('password.update') }}" class="space-y-5">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <x-input name="email" type="email" label="Email address" :value="old('email', $email)" required />
    <x-input name="password" type="password" label="New password" placeholder="Min. 8 characters" required />
    <x-input name="password_confirmation" type="password" label="Confirm password" required />
    <x-btn type="submit" class="w-full justify-center">Reset password</x-btn>
</form>
@endsection
