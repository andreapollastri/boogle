@extends('layouts.app')
@section('title', 'New project')

@section('content')
<x-page-header title="New project" description="Create a project, then follow the installation guide to start reporting."
    :breadcrumbs="[['label'=>'Projects','href'=>route('panel.projects.index')],['label'=>'New project']]" />

<div class="max-w-2xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('panel.projects.store') }}" class="space-y-5">
            @csrf
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-input name="title" label="Project name" placeholder="My Laravel App" required autofocus />
                <x-input name="url" type="url" label="Project URL" placeholder="https://myapp.com" />
            </div>
            <div class="space-y-4 rounded-xl border border-gray-200 bg-gray-50/50 p-4" x-data="{ enabled: @js((bool) old('uptime_enabled', true)) }">
                <label class="flex items-start gap-3">
                    <input type="hidden" name="uptime_enabled" value="0">
                    <input type="checkbox" name="uptime_enabled" value="1" x-model="enabled" class="mt-0.5">
                    <span>
                        <span class="block text-sm font-medium text-gray-800">Uptime monitoring</span>
                        <span class="block text-xs text-gray-500">Ping the URL every minute and open an outage issue when it stops responding.</span>
                    </span>
                </label>
                <div x-show="enabled" x-transition>
                    <x-input name="uptime_url" type="url" label="Health-check URL (optional)" placeholder="https://myapp.com/up" hint="Leave empty to ping the project URL." />
                </div>
            </div>
            <x-textarea name="description" label="Description" placeholder="What is this project?" />

            @if(auth()->user()->isAdmin() && $groups->count())
                <div>
                    <label for="group_id" class="mb-1.5 block text-sm font-medium text-gray-700">Group</label>
                    <select name="group_id" id="group_id" class="field">
                        <option value="">No group</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ old('group_id') === $group->id ? 'selected' : '' }}>{{ $group->title }}{{ $group->issue_prefix ? ' — #'.strtoupper($group->issue_prefix) : '' }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="flex gap-3 border-t border-gray-100 pt-5">
                <x-btn type="submit">Create project</x-btn>
                <x-btn href="{{ route('panel.projects.index') }}" variant="ghost">Cancel</x-btn>
            </div>
        </form>
    </div>
</div>
@endsection
