@extends('layouts.app')
@section('title', 'Users')

@section('content')
<x-page-header title="Users" description="Admins see every project. Basic users see the projects and groups assigned to them.">
    <x-slot:actions>
        <x-btn href="{{ route('panel.users.create') }}">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New user
        </x-btn>
    </x-slot:actions>
</x-page-header>

<div class="card overflow-hidden">
    <ul class="divide-y divide-gray-100">
        @foreach($users as $item)
            <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3.5">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full {{ $item->isAdmin() ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600' }} text-sm font-semibold">
                    {{ mb_strtoupper(mb_substr($item->name, 0, 1)) }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="flex items-center gap-2 truncate text-sm font-medium text-gray-900">
                        {{ $item->name }}
                        @if($item->id === auth()->id())
                            <span class="text-xs font-normal text-gray-400">(you)</span>
                        @endif
                    </p>
                    <p class="truncate text-xs text-gray-500">{{ $item->email }}</p>
                </div>
                <div class="hidden w-40 text-xs text-gray-500 md:block">
                    @if($item->isAdmin())
                        All projects &amp; groups
                    @else
                        {{ $item->projects_count }} {{ Str::plural('project', $item->projects_count) }} · {{ $item->assigned_groups_count }} {{ Str::plural('group', $item->assigned_groups_count) }}
                    @endif
                </div>
                <div class="w-16">
                    <x-badge :variant="$item->isAdmin() ? 'indigo' : 'gray'">{{ $item->isAdmin() ? 'Admin' : 'Basic' }}</x-badge>
                </div>
                <div class="flex items-center gap-2">
                    <x-btn href="{{ route('panel.users.edit', $item) }}" variant="secondary" size="sm">Edit</x-btn>
                    @if($item->id !== auth()->id())
                        <x-confirm-delete :action="route('panel.users.destroy', $item)"
                                          :title="'Delete user “'.$item->name.'”?'"
                                          message="This action is permanent. The user account and its project and group assignments will be removed." />
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
</div>
@endsection
