@extends('layouts.app')
@section('title', 'Projects')

@section('content')
<x-page-header title="Projects" description="Every application Boogle watches for exceptions and downtime.">
    <x-slot:actions>
        <x-btn href="{{ route('panel.projects.create') }}">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New project
        </x-btn>
    </x-slot:actions>
</x-page-header>

@if($projects->isEmpty() && ! $selectedGroupId)
    <div class="card border-dashed py-20 text-center">
        <svg class="mx-auto mb-4 size-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h3l2 2h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
        <h3 class="mb-2 text-base font-medium text-gray-900">No projects yet</h3>
        <p class="mb-6 text-sm text-gray-500">Create your first project to start tracking exceptions.</p>
        <x-btn href="{{ route('panel.projects.create') }}">Create project</x-btn>
    </div>
@else
    @php
        $searchIndex = $projects->map(fn ($p) => mb_strtolower(implode(' ', array_filter([$p->title, $p->url, $p->group?->title, $p->description]))))->values();
    @endphp
    <div x-data="{ q: '', index: @js($searchIndex), matches(i) { return ! this.q || this.index[i].includes(this.q.toLowerCase()) }, get count() { return this.index.filter((_, i) => this.matches(i)).length } }">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative sm:w-72">
                <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" x-model.debounce.100ms="q" data-shortcut="search" placeholder="Filter projects…" class="field pl-9 pr-10">
                <span class="kbd pointer-events-none absolute top-1/2 right-2.5 -translate-y-1/2">/</span>
            </div>

            @if(auth()->user()->isAdmin() && $groups->isNotEmpty())
                <div class="flex flex-wrap items-center gap-1.5">
                    <a href="{{ route('panel.projects.index') }}"
                       class="rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset transition {{ ! $selectedGroupId ? 'bg-gray-900 text-white ring-gray-900' : 'bg-white text-gray-600 ring-gray-200 hover:ring-gray-300' }}">
                        All
                    </a>
                    @foreach($groups as $group)
                        <a href="{{ route('panel.projects.index', ['group_id' => $group->id]) }}"
                           class="rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset transition {{ $selectedGroupId === $group->id ? 'bg-gray-900 text-white ring-gray-900' : 'bg-white text-gray-600 ring-gray-200 hover:ring-gray-300' }}">
                            {{ $group->title }} <span class="ml-0.5 opacity-60">{{ $group->projects_count }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if($projects->isEmpty())
            <div class="card border-dashed py-14 text-center text-sm text-gray-500">No projects in this group yet.</div>
        @endif

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 2xl:grid-cols-3">
            @foreach($projects as $project)
                @php
                    $health = $project->healthStatus();
                    $host = $project->url ? (parse_url($project->url, PHP_URL_HOST) ?: $project->url) : null;
                @endphp
                <div x-show="matches({{ $loop->index }})"
                     class="card group relative flex flex-col transition hover:border-indigo-300 hover:shadow-md hover:shadow-indigo-500/5 {{ $health === 'offline' ? 'border-red-200' : '' }}">
                    <div class="flex-1 p-5">
                        <div class="flex items-start gap-3">
                            <div class="relative flex size-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-sm font-bold text-white">
                                {{ mb_strtoupper(mb_substr($project->title, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate text-sm font-semibold text-gray-900">
                                    <a href="{{ route('panel.projects.show', $project) }}" class="after:absolute after:inset-0 after:rounded-2xl focus:outline-none">
                                        {{ $project->title }}
                                    </a>
                                </h3>
                                <p class="truncate text-xs text-gray-500">{{ $host ?? 'No URL' }}@if($project->group) · {{ $project->group->title }}@endif</p>
                            </div>
                            <x-health-badge :status="$health" />
                        </div>

                        <div class="mt-5 grid grid-cols-3 gap-3 text-xs">
                            <div>
                                <p class="text-gray-500">Open</p>
                                <p class="mt-0.5 text-lg font-semibold tabular-nums {{ $project->open_count ? 'text-red-600' : 'text-gray-900' }}">{{ $project->open_count }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Total</p>
                                <p class="mt-0.5 text-lg font-semibold tabular-nums text-gray-900">{{ number_format($project->total_exceptions) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Last error</p>
                                <p class="mt-0.5 truncate text-sm font-medium leading-7 text-gray-900">{{ $project->last_error_at?->diffForHumans(short: true) ?? '—' }}</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <x-sparkline :series="$activity[$project->id] ?? []" :height="26" />
                            <p class="mt-1 text-[10px] text-gray-400">Exceptions, last 14 days</p>
                        </div>
                    </div>
                    <div class="relative z-10 flex items-center gap-4 rounded-b-2xl border-t border-gray-100 bg-gray-50/70 px-5 py-2.5 text-xs">
                        <a href="{{ route('panel.projects.exceptions.index', [$project, 'status' => $project->open_count ? 'OPEN' : null]) }}" class="font-medium text-indigo-600 hover:text-indigo-800">
                            {{ $project->open_count ? 'Triage '.$project->open_count.' open' : 'Exceptions' }} →
                        </a>
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('panel.projects.installation', $project) }}" class="ml-auto text-gray-500 hover:text-gray-800">Install</a>
                            <a href="{{ route('panel.projects.edit', $project) }}" class="text-gray-500 hover:text-gray-800">Settings</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <p x-show="q && count === 0" x-cloak class="py-10 text-center text-sm text-gray-500">No projects match “<span x-text="q"></span>”.</p>
    </div>
@endif
@endsection
