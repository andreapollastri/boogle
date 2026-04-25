@extends('layouts.app')
@section('title', 'Projects')

@section('content')
<x-page-header title="Projects" description="Manage your error tracking projects">
    <x-slot:actions>
        <x-btn href="{{ route('panel.projects.create') }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New project
        </x-btn>
    </x-slot:actions>
</x-page-header>

@if(auth()->user()->isAdmin() && $groups->isNotEmpty())
    <div class="mb-5 bg-white rounded-2xl border border-gray-200 p-4">
        <form method="GET" action="{{ route('panel.projects.index') }}" class="flex items-end gap-3">
            <div class="min-w-64">
                <label for="group_id" class="block text-xs font-medium text-gray-600 mb-1.5">Filter by group</label>
                <select id="group_id" name="group_id" class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">All groups</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" {{ $selectedGroupId === $group->id ? 'selected' : '' }}>{{ $group->title }}</option>
                    @endforeach
                </select>
            </div>
            <x-btn type="submit">Apply</x-btn>
            @if($selectedGroupId)
                <x-btn href="{{ route('panel.projects.index') }}" variant="secondary">Reset</x-btn>
            @endif
        </form>
    </div>
@endif

@if($projects->isEmpty())
    <div class="bg-white rounded-2xl border border-dashed border-gray-300 py-20 text-center">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h3l2 2h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
        <h3 class="text-base font-medium text-gray-900 mb-2">No projects yet</h3>
        <p class="text-sm text-gray-500 mb-6">Create your first project to start tracking exceptions.</p>
        <x-btn href="{{ route('panel.projects.create') }}">Create project</x-btn>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach($projects as $project)
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden hover:border-indigo-300 hover:shadow-md transition-all">
                <div class="p-6">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0">
                                <span class="text-sm font-bold text-indigo-700">{{ strtoupper($project->title[0]) }}</span>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-gray-900 truncate">
                                    <a href="{{ route('panel.projects.show', $project) }}" class="hover:text-indigo-700">
                                        {{ $project->title }}
                                    </a>
                                </h3>
                                @if($project->url)
                                    <p class="text-xs text-gray-500 truncate">{{ $project->url }}</p>
                                @endif
                            </div>
                        </div>
                        @if($project->open_count > 0)
                            <x-badge variant="red">{{ $project->open_count }}</x-badge>
                        @else
                            <x-badge variant="green">OK</x-badge>
                        @endif
                    </div>
                    @if($project->description)
                        <p class="text-xs text-gray-500 mb-4 line-clamp-2">{{ $project->description }}</p>
                    @endif
                    <div class="flex items-center justify-between text-xs text-gray-400">
                        <span>{{ number_format($project->total_exceptions) }} exceptions</span>
                        @if($project->last_error_at)
                            <span>Last: {{ $project->last_error_at->diffForHumans() }}</span>
                        @endif
                    </div>
                </div>
                <div class="border-t border-gray-100 px-6 py-3 bg-gray-50 flex items-center gap-4">
                    <a href="{{ route('panel.projects.exceptions.index', $project) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">View exceptions →</a>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('panel.projects.edit', $project) }}" class="text-xs text-gray-500 hover:text-gray-700 ml-auto">Settings</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
