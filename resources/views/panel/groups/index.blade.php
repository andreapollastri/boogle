@extends('layouts.app')
@section('title', 'Groups')

@section('content')
<x-page-header title="Project Groups" description="Organize your projects into groups">
    <x-slot:actions>
        <x-btn x-data @click="$dispatch('open-modal', 'create-group')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New group
        </x-btn>
    </x-slot:actions>
</x-page-header>

@if($groups->isEmpty())
    <div class="bg-white rounded-2xl border border-dashed border-gray-300 py-20 text-center">
        <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        <h3 class="text-sm font-medium text-gray-900 mb-2">No groups yet</h3>
        <p class="text-xs text-gray-500 mb-5">Groups help you organize projects together.</p>
        <x-btn x-data @click="$dispatch('open-modal', 'create-group')">Create your first group</x-btn>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach($groups as $group)
            <div class="bg-white rounded-2xl border border-gray-200 p-6 cursor-pointer hover:border-indigo-300 hover:shadow-md transition-all"
                 onclick="window.location.href='{{ route('panel.projects.index', ['group_id' => $group->id]) }}'">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">{{ $group->title }}</h3>
                        @if($group->description)
                            <p class="text-xs text-gray-500 mt-1">{{ $group->description }}</p>
                        @endif
                        @if($group->issue_prefix)
                            <p class="text-xs text-indigo-600 mt-1 font-mono">Issue prefix: #{{ strtoupper($group->issue_prefix) }}…</p>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <button x-data @click.stop="$dispatch('open-modal', 'edit-group-{{ $group->id }}')" class="text-gray-400 hover:text-gray-600 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <div x-data="{ openDeleteGroupModal: false }">
                            <button type="button" @click.stop="openDeleteGroupModal = true" class="text-gray-400 hover:text-red-600 p-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>

                            <div x-show="openDeleteGroupModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                <div class="fixed inset-0 bg-black/40" @click="openDeleteGroupModal = false"></div>
                                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
                                    <div class="flex items-start gap-3">
                                        <div class="mt-0.5 w-9 h-9 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-base font-semibold text-gray-900">Delete group “{{ $group->title }}”?</h3>
                                            <p class="mt-2 text-sm text-gray-600">
                                                This action is permanent. Group assignments will be removed and projects in this group will lose the group association.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-6 flex items-center justify-end gap-3">
                                        <x-btn variant="secondary" type="button" @click="openDeleteGroupModal = false">Cancel</x-btn>
                                        <form method="POST" action="{{ route('panel.groups.destroy', $group) }}">
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
                </div>
                <p class="text-xs text-gray-400">{{ $group->projects_count }} project{{ $group->projects_count !== 1 ? 's' : '' }}</p>
            </div>

            {{-- Edit modal --}}
            <div x-data="modal('edit-group-{{ $group->id }}')" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/40" @click="open=false"></div>
                <div class="relative bg-white rounded-2xl shadow-xl p-6 w-full max-w-md">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-base font-semibold text-gray-900">Edit group</h3>
                        <button @click="open=false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('panel.groups.update', $group) }}" class="space-y-4">
                        @csrf @method('PATCH')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Name</label>
                            <input type="text" name="title" value="{{ $group->title }}" required class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
                            <textarea name="description" rows="2" class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">{{ $group->description }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Issue prefix <span class="text-gray-400 font-normal">(optional)</span></label>
                            <p class="text-xs text-gray-500 mb-1.5">Letters/numbers, max 8. Leave empty for <span class="font-mono">#BUG…</span> on app errors. <span class="font-mono">OUT</span> is reserved for uptime.</p>
                            <input type="text" name="issue_prefix" value="{{ $group->issue_prefix }}" maxlength="8" class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="e.g. SITE, DOC" />
                        </div>
                        <div class="flex gap-3">
                            <x-btn type="submit">Save</x-btn>
                            <x-btn variant="secondary" type="button" @click="open=false">Cancel</x-btn>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- Create modal --}}
<div x-data="modal('create-group')" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-black/40" @click="open=false"></div>
    <div class="relative bg-white rounded-2xl shadow-xl p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-base font-semibold text-gray-900">New group</h3>
            <button @click="open=false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('panel.groups.store') }}" class="space-y-4">
            @csrf
            <x-input name="title" label="Group name" required />
            <x-textarea name="description" label="Description" />
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Issue prefix <span class="text-gray-400 font-normal">(optional)</span></label>
                <p class="text-xs text-gray-500 mb-1.5">Empty = <span class="font-mono">#BUG…</span> for errors. Custom e.g. <span class="font-mono">SITE</span> → <span class="font-mono">#SITE1</span>. <span class="font-mono">OUT</span> is reserved for outages.</p>
                <input type="text" name="issue_prefix" value="{{ old('issue_prefix') }}" maxlength="8" class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="e.g. SITE" />
            </div>
            <div class="flex gap-3">
                <x-btn type="submit">Create group</x-btn>
                <x-btn variant="secondary" type="button" @click="open=false">Cancel</x-btn>
            </div>
        </form>
    </div>
</div>

<script>
function modal(name) {
    return {
        open: false,
        init() {
            window.addEventListener('open-modal', (e) => { if (e.detail === name) this.open = true; });
        }
    }
}
</script>
@endsection
