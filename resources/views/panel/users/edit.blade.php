@extends('layouts.app')
@section('title', 'Edit user')

@section('content')
<x-page-header :title="'Edit ' . $userModel->name" :breadcrumbs="[['label'=>'Users','href'=>route('panel.users.index')],['label'=>$userModel->name]]" />

<div class="max-w-3xl">
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('panel.users.update', $userModel) }}" class="space-y-5">
            @csrf @method('PATCH')

            <x-input name="name" label="Name" :value="old('name', $userModel->name)" required />
            <x-input name="email" type="email" label="Email" :value="old('email', $userModel->email)" required />

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
                <select id="role" name="role" class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="basic" {{ old('role', $userModel->isAdmin() ? 'admin' : 'basic') === 'basic' ? 'selected' : '' }}>Basic</option>
                    <option value="admin" {{ old('role', $userModel->isAdmin() ? 'admin' : 'basic') === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>

            @if(! $userModel->isAdmin())
                <input type="hidden" name="manage_assignments" value="1">
                @php($assignedProjects = old('project_ids', $userModel->projects->pluck('id')->all()))
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Assigned projects</label>
                    @if($userModel->projects->isEmpty() && $projects->isEmpty())
                        <p class="text-xs text-gray-500">No projects available.</p>
                    @else
                        <div class="max-h-44 overflow-auto rounded-lg border border-gray-300 divide-y divide-gray-100">
                            @foreach($projects as $project)
                                <label class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700">
                                    <input type="checkbox" name="project_ids[]" value="{{ $project->id }}"
                                           {{ in_array($project->id, $assignedProjects, true) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    <span>{{ $project->title }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                @php($assignedGroups = old('group_ids', $userModel->assignedGroups->pluck('id')->all()))
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Assigned groups</label>
                    @if($userModel->assignedGroups->isEmpty() && $groups->isEmpty())
                        <p class="text-xs text-gray-500">No groups available.</p>
                    @else
                        <div class="max-h-44 overflow-auto rounded-lg border border-gray-300 divide-y divide-gray-100">
                            @foreach($groups as $group)
                                <label class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700">
                                    <input type="checkbox" name="group_ids[]" value="{{ $group->id }}"
                                           {{ in_array($group->id, $assignedGroups, true) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    <span>{{ $group->title }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <div class="flex gap-3">
                <x-btn type="submit">Save changes</x-btn>
                <x-btn href="{{ route('panel.users.index') }}" variant="secondary">Cancel</x-btn>
            </div>
        </form>
    </div>
</div>
@endsection
