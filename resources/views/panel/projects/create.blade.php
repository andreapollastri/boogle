@extends('layouts.app')
@section('title', 'New project')

@section('content')
<x-page-header title="New project"
    :breadcrumbs="[['label'=>'Projects','href'=>route('panel.projects.index')],['label'=>'New project']]" />

<div class="max-w-xl">
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('panel.projects.store') }}" class="space-y-5">
            @csrf
            <x-input name="title" label="Project name" placeholder="My Laravel App" required />
            <x-input name="url" type="url" label="Project URL" placeholder="https://myapp.com" />
            <div class="rounded-xl border border-gray-200 p-4 space-y-4">
                <label class="flex items-start gap-3">
                    <input type="hidden" name="uptime_enabled" value="0">
                    <input type="checkbox" name="uptime_enabled" value="1" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                           {{ old('uptime_enabled', true) ? 'checked' : '' }}>
                    <span>
                        <span class="block text-sm font-medium text-gray-800">Enable uptime ping</span>
                        <span class="block text-xs text-gray-500">Check project availability and create an exception when offline.</span>
                    </span>
                </label>
                <x-input name="uptime_url" type="url" label="Custom uptime URL (optional)" placeholder="https://status.myapp.com/health" />
                <p class="text-xs text-gray-500">If empty, uptime checks use the project URL.</p>
            </div>
            <x-textarea name="description" label="Description" placeholder="What is this project?" />

            @if(auth()->user()->isAdmin() && $groups->count())
                <div>
                    <label for="group_id" class="block text-sm font-medium text-gray-700 mb-1.5">Group</label>
                    <select name="group_id" id="group_id" class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">No group</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ old('group_id') === $group->id ? 'selected' : '' }}>{{ $group->title }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="flex gap-3 pt-2">
                <x-btn type="submit">Create project</x-btn>
                <x-btn href="{{ route('panel.projects.index') }}" variant="secondary">Cancel</x-btn>
            </div>
        </form>
    </div>
</div>
@endsection
