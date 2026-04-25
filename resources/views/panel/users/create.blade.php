@extends('layouts.app')
@section('title', 'New user')

@section('content')
<x-page-header title="New user" :breadcrumbs="[['label'=>'Users','href'=>route('panel.users.index')],['label'=>'New user']]" />

<div class="max-w-3xl">
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('panel.users.store') }}" class="space-y-5">
            @csrf

            <x-input name="name" label="Name" required />
            <x-input name="email" type="email" label="Email" required />
            @php($generatedPassword = old('generated_password', $proposedPassword))
            <div>
                <label for="generated_password" class="block text-sm font-medium text-gray-700 mb-1.5">Generated password</label>
                <input id="generated_password"
                       name="generated_password"
                       type="text"
                       value="{{ $generatedPassword }}"
                       readonly
                       class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-3.5 py-2.5 text-sm font-mono text-gray-900" />
                <p class="mt-1 text-xs text-gray-500">
                    This password is generated to satisfy security rules. Share it with the user and ask them to change it after first login.
                </p>
            </div>
            <input type="hidden" name="password" value="{{ $generatedPassword }}">
            <input type="hidden" name="password_confirmation" value="{{ $generatedPassword }}">

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
                <select id="role" name="role" class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="basic" {{ old('role', 'basic') === 'basic' ? 'selected' : '' }}>Basic</option>
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>

            <div class="flex gap-3">
                <x-btn type="submit">Create user</x-btn>
                <x-btn href="{{ route('panel.users.index') }}" variant="secondary">Cancel</x-btn>
            </div>
        </form>
    </div>
</div>
@endsection
