@extends('layouts.app')
@section('title', 'Edit ' . $project->title)

@section('content')
<x-page-header :title="'Edit ' . $project->title"
    :breadcrumbs="[
        ['label'=>'Projects','href'=>route('panel.projects.index')],
        ['label'=>$project->title,'href'=>route('panel.projects.show',$project)],
        ['label'=>'Settings']
    ]" />

<div class="max-w-2xl space-y-6">
    {{-- General --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-5">General</h3>
        <form method="POST" action="{{ route('panel.projects.update', $project) }}" class="space-y-5">
            @csrf @method('PATCH')
            <x-input name="title" label="Project name" :value="old('title', $project->title)" required />
            <x-input name="url" type="url" label="Project URL" placeholder="https://" :value="old('url', $project->url)" />
            <div class="rounded-xl border border-gray-200 p-4 space-y-4">
                <label class="flex items-start gap-3">
                    <input type="hidden" name="uptime_enabled" value="0">
                    <input type="checkbox" name="uptime_enabled" value="1" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                           {{ old('uptime_enabled', $project->uptime_enabled) ? 'checked' : '' }}>
                    <span>
                        <span class="block text-sm font-medium text-gray-800">Enable uptime ping</span>
                        <span class="block text-xs text-gray-500">Create an offline exception when this endpoint does not respond correctly.</span>
                    </span>
                </label>
                <x-input name="uptime_url" type="url" label="Custom uptime URL (optional)" placeholder="https://status.myapp.com/health"
                         :value="old('uptime_url', $project->uptime_url)" />
                <p class="text-xs text-gray-500">If empty, uptime checks use the project URL.</p>
            </div>
            <x-textarea name="description" label="Description" :value="old('description', $project->description)" />

            @if(auth()->user()->isAdmin() && $groups->count())
                <div>
                    <label for="group_id" class="block text-sm font-medium text-gray-700 mb-1.5">Group</label>
                    <select name="group_id" id="group_id" class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">No group</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ old('group_id', $project->group_id) === $group->id ? 'selected' : '' }}>{{ $group->title }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <x-btn type="submit">Save changes</x-btn>
        </form>
    </div>

    @if(auth()->user()->isAdmin())
        {{-- Project API Token --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-1">Project API Token</h3>
            <p class="text-xs text-gray-500 mb-4">This token is used only by this project integration.</p>
            <div class="flex items-center gap-3">
                <code class="flex-1 text-xs font-mono bg-gray-100 px-3 py-2.5 rounded-lg text-gray-800 break-all select-all">{{ $project->api_token }}</code>
            </div>
            <form method="POST" action="{{ route('panel.projects.token', $project) }}" class="mt-3"
                  onsubmit="return confirm('Regenerate project token? Existing app installations for this project will stop reporting until updated.')">
                @csrf
                <x-btn type="submit" variant="secondary">Regenerate token</x-btn>
            </form>
        </div>
    @endif
</div>
@endsection
