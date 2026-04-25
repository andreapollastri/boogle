@extends('layouts.app')
@section('title', 'Users')

@section('content')
<x-page-header title="Users" description="Manage admin and basic users">
    <x-slot:actions>
        <x-btn href="{{ route('panel.users.create') }}">New user</x-btn>
    </x-slot:actions>
</x-page-header>

{{-- Mobile card list --}}
<div class="sm:hidden space-y-3">
    @foreach($users as $item)
        <div class="bg-white rounded-2xl border border-gray-200 p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-medium text-gray-900 truncate">{{ $item->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ $item->email }}</p>
                </div>
                @if($item->isAdmin())
                    <x-badge variant="indigo">Admin</x-badge>
                @else
                    <x-badge variant="gray">Basic</x-badge>
                @endif
            </div>
            <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
                <span>Projects: <span class="text-gray-700 font-medium">{{ $item->isAdmin() ? 'All' : $item->projects_count }}</span></span>
                <span>Groups: <span class="text-gray-700 font-medium">{{ $item->isAdmin() ? 'All' : $item->assigned_groups_count }}</span></span>
            </div>
            <div x-data="{ openDeleteUserModal: false }" class="mt-3 flex items-center gap-2">
                <a href="{{ route('panel.users.edit', $item) }}" class="text-xs px-2.5 py-1.5 rounded border border-gray-300 text-gray-700 hover:bg-gray-50">Edit</a>
                <button type="button"
                        @click="openDeleteUserModal = true"
                        class="text-xs px-2.5 py-1.5 rounded border border-red-300 text-red-600 hover:bg-red-50">
                    Delete
                </button>

                <div x-show="openDeleteUserModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/40" @click="openDeleteUserModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 w-9 h-9 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">Delete user "{{ $item->name }}"?</h3>
                                <p class="mt-2 text-sm text-gray-600">
                                    This action is permanent. The user account and direct user assignments will be removed.
                                </p>
                            </div>
                        </div>
                        <div class="mt-6 flex items-center justify-end gap-3">
                            <button type="button" @click="openDeleteUserModal = false"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 shadow-sm transition-all">
                                Cancel
                            </button>
                            <form method="POST" action="{{ route('panel.users.destroy', $item) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700 transition-colors">
                                    Delete permanently
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Desktop table --}}
<div class="hidden sm:block bg-white rounded-2xl border border-gray-200 overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-4 py-3 font-medium text-gray-600">User</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Role</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Projects</th>
                <th class="text-left px-4 py-3 font-medium text-gray-600">Groups</th>
                <th class="text-right px-4 py-3 font-medium text-gray-600">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($users as $item)
                <tr>
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900">{{ $item->name }}</p>
                        <p class="text-xs text-gray-500">{{ $item->email }}</p>
                    </td>
                    <td class="px-4 py-3">
                        @if($item->isAdmin())
                            <x-badge variant="indigo">Admin</x-badge>
                        @else
                            <x-badge variant="gray">Basic</x-badge>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ $item->isAdmin() ? 'All' : $item->projects_count }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $item->isAdmin() ? 'All' : $item->assigned_groups_count }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('panel.users.edit', $item) }}" class="text-xs px-2.5 py-1.5 rounded border border-gray-300 text-gray-700 hover:bg-gray-50">Edit</a>
                            <div x-data="{ openDeleteUserModal: false }">
                                <button type="button"
                                        @click="openDeleteUserModal = true"
                                        class="text-xs px-2.5 py-1.5 rounded border border-red-300 text-red-600 hover:bg-red-50">
                                    Delete
                                </button>

                                <div x-show="openDeleteUserModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                    <div class="fixed inset-0 bg-black/40" @click="openDeleteUserModal = false"></div>
                                    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
                                        <div class="flex items-start gap-3">
                                            <div class="mt-0.5 w-9 h-9 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-base font-semibold text-gray-900">Delete user "{{ $item->name }}"?</h3>
                                                <p class="mt-2 text-sm text-gray-600">
                                                    This action is permanent. The user account and direct user assignments will be removed.
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-6 flex items-center justify-end gap-3">
                                            <button type="button" @click="openDeleteUserModal = false"
                                                    class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 shadow-sm transition-all">
                                                Cancel
                                            </button>
                                            <form method="POST" action="{{ route('panel.users.destroy', $item) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700 transition-colors">
                                                    Delete permanently
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
