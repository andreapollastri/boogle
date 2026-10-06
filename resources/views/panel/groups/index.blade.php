@extends('layouts.app')
@section('title', 'Groups')

@section('content')
<x-page-header title="Groups" description="Group related projects, share access with a team and give their issues a common prefix.">
    <x-slot:actions>
        <x-btn x-data @click="$dispatch('open-modal', 'create-group')">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New group
        </x-btn>
    </x-slot:actions>
</x-page-header>

@if($groups->isEmpty())
    <div class="card border-dashed py-20 text-center">
        <svg class="mx-auto mb-3 size-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        <h3 class="mb-2 text-sm font-medium text-gray-900">No groups yet</h3>
        <p class="mb-5 text-xs text-gray-500">Groups help you organize projects together.</p>
        <x-btn x-data @click="$dispatch('open-modal', 'create-group')">Create your first group</x-btn>
    </div>
@else
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 2xl:grid-cols-3">
        @foreach($groups as $group)
            <div class="card group relative flex flex-col p-5 transition hover:border-indigo-300 hover:shadow-md hover:shadow-indigo-500/5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-gray-900">
                            <a href="{{ route('panel.projects.index', ['group_id' => $group->id]) }}" class="after:absolute after:inset-0 after:rounded-2xl">{{ $group->title }}</a>
                        </h3>
                        @if($group->description)
                            <p class="mt-1 text-xs text-gray-500">{{ $group->description }}</p>
                        @endif
                    </div>
                    <span class="shrink-0 rounded-md border border-indigo-100 bg-indigo-50 px-2 py-0.5 font-mono text-[11px] font-semibold text-indigo-700" title="Issue code prefix">
                        #{{ $group->issue_prefix ? strtoupper($group->issue_prefix) : 'BUG' }}
                    </span>
                </div>

                <div class="mt-4 flex flex-wrap gap-1.5">
                    @forelse($group->projects as $project)
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600">{{ $project->title }}</span>
                    @empty
                        <span class="text-xs text-gray-400">No projects yet</span>
                    @endforelse
                </div>

                <div class="relative z-10 mt-auto flex items-center gap-3 pt-4 text-xs text-gray-500">
                    <span>{{ $group->projects_count }} {{ Str::plural('project', $group->projects_count) }} · {{ $group->assigned_users_count }} {{ Str::plural('member', $group->assigned_users_count) }}</span>
                    <div class="ml-auto flex items-center gap-1">
                        <button type="button" x-data @click="$dispatch('open-modal', 'edit-group-{{ $group->id }}')" class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700" title="Edit group" aria-label="Edit group">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <x-confirm-delete :action="route('panel.groups.destroy', $group)"
                                          :title="'Delete group “'.$group->title.'”?'"
                                          message="This action is permanent. Group assignments will be removed and its projects will no longer belong to a group."
                                          trigger-class="rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600"
                                          aria-label="Delete group">
                            <x-slot:trigger-slot>
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </x-slot:trigger-slot>
                        </x-confirm-delete>
                    </div>
                </div>
            </div>

            {{-- Edit modal --}}
            <div x-data="modal('edit-group-{{ $group->id }}')" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="open = false">
                <div class="fixed inset-0 bg-gray-900/40" @click="open = false"></div>
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                    <div class="mb-5 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Edit group</h3>
                        <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600" aria-label="Close">
                            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('panel.groups.update', $group) }}" class="space-y-4">
                        @csrf @method('PATCH')
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" name="title" value="{{ $group->title }}" required class="field" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" rows="2" class="field">{{ $group->description }}</textarea>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Issue prefix <span class="font-normal text-gray-400">(optional)</span></label>
                            <input type="text" name="issue_prefix" value="{{ $group->issue_prefix }}" maxlength="8" class="field font-mono uppercase" placeholder="e.g. SITE, DOC" />
                            <p class="mt-1.5 text-xs text-gray-500">Letters/numbers, max 8. Empty = <span class="font-mono">#BUG…</span>. <span class="font-mono">OUT</span> is reserved for uptime.</p>
                        </div>
                        <div class="flex gap-3">
                            <x-btn type="submit">Save</x-btn>
                            <x-btn variant="ghost" type="button" @click="open = false">Cancel</x-btn>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- Create modal --}}
<div x-data="modal('create-group', @js($errors->any()))" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="open = false">
    <div class="fixed inset-0 bg-gray-900/40" @click="open = false"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div class="mb-5 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900">New group</h3>
            <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600" aria-label="Close">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('panel.groups.store') }}" class="space-y-4">
            @csrf
            <x-input name="title" label="Group name" required />
            <x-textarea name="description" label="Description" />
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Issue prefix <span class="font-normal text-gray-400">(optional)</span></label>
                <input type="text" name="issue_prefix" value="{{ old('issue_prefix') }}" maxlength="8" class="field font-mono uppercase" placeholder="e.g. SITE" />
                @error('issue_prefix')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                <p class="mt-1.5 text-xs text-gray-500">Empty = <span class="font-mono">#BUG…</span>. Custom e.g. <span class="font-mono">SITE</span> → <span class="font-mono">#SITE1</span>. <span class="font-mono">OUT</span> is reserved for outages.</p>
            </div>
            <div class="flex gap-3">
                <x-btn type="submit">Create group</x-btn>
                <x-btn variant="ghost" type="button" @click="open = false">Cancel</x-btn>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function modal(name, initiallyOpen = false) {
    return {
        open: initiallyOpen,
        init() {
            window.addEventListener('open-modal', (e) => { if (e.detail === name) this.open = true; });
        }
    }
}
</script>
@endpush
@endsection
