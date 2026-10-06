@extends('layouts.app')
@section('title', 'Settings — ' . $project->title)

@section('content')
<x-project-header :project="$project" active="settings" />

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <div class="card p-6">
            <h2 class="card-title">General</h2>
            <p class="mb-5 text-xs text-gray-500">Name, URL and how Boogle checks that the project is online.</p>
            <form method="POST" action="{{ route('panel.projects.update', $project) }}" class="space-y-5">
                @csrf @method('PATCH')
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-input name="title" label="Project name" :value="old('title', $project->title)" required />
                    <x-input name="url" type="url" label="Project URL" placeholder="https://" :value="old('url', $project->url)" />
                </div>
                <div class="space-y-4 rounded-xl border border-gray-200 bg-gray-50/50 p-4" x-data="{ enabled: @js((bool) old('uptime_enabled', $project->uptime_enabled)) }">
                    <label class="flex items-start gap-3">
                        <input type="hidden" name="uptime_enabled" value="0">
                        <input type="checkbox" name="uptime_enabled" value="1" x-model="enabled" class="mt-0.5">
                        <span>
                            <span class="block text-sm font-medium text-gray-800">Uptime monitoring</span>
                            <span class="block text-xs text-gray-500">Ping the URL every minute. An outage opens a <span class="font-mono">#OUT</span> issue and emails the team; recovery closes it automatically.</span>
                        </span>
                    </label>
                    <div x-show="enabled" x-transition>
                        <x-input name="uptime_url" type="url" label="Health-check URL (optional)" placeholder="https://myapp.com/up"
                                 :value="old('uptime_url', $project->uptime_url)" hint="Leave empty to ping the project URL." />
                    </div>
                </div>
                <x-textarea name="description" label="Description" :value="old('description', $project->description)" />

                @if($groups->count())
                    <div>
                        <label for="group_id" class="mb-1.5 block text-sm font-medium text-gray-700">Group</label>
                        <select name="group_id" id="group_id" class="field">
                            <option value="">No group</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" {{ old('group_id', $project->group_id) === $group->id ? 'selected' : '' }}>{{ $group->title }}{{ $group->issue_prefix ? ' — #'.strtoupper($group->issue_prefix) : '' }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-gray-500">Projects in a group share its issue prefix and numbering.</p>
                    </div>
                @endif

                <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
                    <x-btn type="submit">Save changes</x-btn>
                    <x-btn href="{{ route('panel.projects.show', $project) }}" variant="ghost">Cancel</x-btn>
                </div>
            </form>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card p-6">
            <h2 class="card-title">Project API token</h2>
            <p class="mb-4 text-xs text-gray-500">Used by <span class="font-mono">boogle-client</span> as <span class="font-mono">BOOGLE_KEY</span>. Only this project accepts it.</p>
            <div class="flex items-center gap-2" x-data="{ reveal: false }">
                <code class="min-w-0 flex-1 truncate rounded-lg bg-gray-100 px-3 py-2 font-mono text-xs text-gray-800"
                      x-text="reveal ? @js($project->api_token) : '•'.repeat(32)">{{ str_repeat('•', 32) }}</code>
                <button type="button" @click="reveal = ! reveal" class="rounded-md border border-gray-200 bg-white px-2 py-1 text-xs font-medium text-gray-600 shadow-sm hover:bg-gray-50" x-text="reveal ? 'Hide' : 'Show'">Show</button>
                <x-copy-button :value="$project->api_token" />
            </div>
            <form method="POST" action="{{ route('panel.projects.token', $project) }}" class="mt-4"
                  onsubmit="return confirm('Regenerate the project token? Apps using the current token will stop reporting until you update BOOGLE_KEY.')">
                @csrf
                <x-btn type="submit" variant="secondary" size="sm">Regenerate token</x-btn>
            </form>
        </div>

        <div class="card border-red-200 p-6">
            <h2 class="text-sm font-semibold text-red-600">Danger zone</h2>
            <p class="mt-1 mb-4 text-xs text-gray-500">Deleting the project removes its exceptions, uptime history and team assignments.</p>
            <x-confirm-delete :action="route('panel.projects.destroy', $project)"
                              :title="'Delete project “'.$project->title.'”?'"
                              message="This action is permanent. The project and all related data will be removed, including exceptions, uptime history and project associations."
                              trigger="Delete this project" />
        </div>
    </div>
</div>
@endsection
