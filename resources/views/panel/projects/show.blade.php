@extends('layouts.app')
@section('title', $project->title)

@section('content')
<x-project-header :project="$project" active="overview">
    <x-slot:actions>
        <x-btn href="{{ route('panel.projects.exceptions.index', [$project, 'status' => ($statusCounts['OPEN'] ?? 0) ? 'OPEN' : null]) }}">
            {{ ($statusCounts['OPEN'] ?? 0) ? 'Triage open exceptions' : 'View exceptions' }}
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </x-btn>
    </x-slot:actions>
</x-project-header>

@php
    $open = (int) ($statusCounts['OPEN'] ?? 0);
    $read = (int) ($statusCounts['READ'] ?? 0);
    $fixed = (int) ($statusCounts['FIXED'] ?? 0);
    $uptime = $uptimeOverview;
@endphp

<div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
    <x-stat label="Open" :value="$open" :tone="$open ? 'danger' : 'success'" hint="Not looked at yet"
            :href="route('panel.projects.exceptions.index', [$project, 'status' => 'OPEN'])" />
    <x-stat label="Read · Fixed" :value="$read.' · '.$fixed" hint="In progress / awaiting verification"
            :href="route('panel.projects.exceptions.index', [$project, 'status' => 'READ'])" />
    <x-stat label="Last 24 hours" :value="$last24h" :hint="$project->last_error_at ? 'Last error '.$project->last_error_at->diffForHumans() : 'No errors yet'" />
    <x-stat label="Uptime (30 days)"
            :value="$uptime['uptime_percent_30d'] !== null ? number_format($uptime['uptime_percent_30d'], 2).'%' : '—'"
            :tone="$uptime['status'] === 'disabled' ? 'muted' : ($uptime['status'] === 'offline' ? 'danger' : 'default')"
            :hint="$uptime['status'] === 'disabled' ? 'Uptime checks are off' : ($uptime['avg_response_ms_30d'] !== null ? 'Avg response '.number_format($uptime['avg_response_ms_30d']).' ms' : 'Waiting for the first checks')" />
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="min-w-0 space-y-6 xl:col-span-2">
        {{-- Unresolved --}}
        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Unresolved exceptions</h2>
                    <p class="text-xs text-gray-500">Open and read, newest first</p>
                </div>
                <a href="{{ route('panel.projects.exceptions.index', $project) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">View all →</a>
            </div>
            @if($unresolved->isEmpty())
                <div class="px-6 py-12 text-center">
                    <p class="text-2xl">✨</p>
                    <p class="mt-2 text-sm font-medium text-gray-900">Nothing to triage</p>
                    <p class="mt-1 text-xs text-gray-500">New exceptions from this project will show up here.</p>
                </div>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach($unresolved as $ex)
                        <li>
                            <a href="{{ route('panel.projects.exceptions.show', [$project, $ex]) }}" class="flex items-start gap-4 px-5 py-3.5 transition-colors hover:bg-gray-50">
                                <span class="mt-0.5 w-16 shrink-0 font-mono text-xs font-medium text-indigo-600">{{ $ex->issue_code ?? '—' }}</span>
                                <span class="min-w-0 flex-1">
                                    <x-exception-name :name="$ex->exception" class="block truncate text-sm" />
                                    <span class="mt-0.5 block truncate text-xs text-gray-500">{{ $ex->error }}</span>
                                </span>
                                <span class="flex shrink-0 flex-col items-end gap-1">
                                    <x-status-badge :status="$ex->status" />
                                    <span class="text-[11px] text-gray-400" title="{{ $ex->created_at->toDayDateTimeString() }}">{{ $ex->created_at->diffForHumans(short: true) }}</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Uptime --}}
        <div class="card p-5">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="card-title">Uptime</h2>
                    <p class="mt-0.5 text-xs text-gray-500">
                        One HTTP check per minute
                        @if($uptime['ping_url'])
                            · <span class="font-mono">{{ $uptime['ping_url'] }}</span>
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-4 text-xs text-gray-500">
                    <span>{{ $uptime['offline_incidents_30d'] }} {{ Str::plural('incident', $uptime['offline_incidents_30d']) }} (30d)</span>
                    <span>Last outage: <span class="font-medium text-gray-700">{{ $uptime['last_outage_at'] ? \Illuminate\Support\Carbon::parse($uptime['last_outage_at'])->diffForHumans() : 'never' }}</span></span>
                </div>
            </div>

            @if(! $project->uptime_enabled)
                <div class="rounded-xl border border-dashed border-gray-200 px-4 py-8 text-center text-sm text-gray-500">
                    Uptime checks are off for this project.
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('panel.projects.edit', $project) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Enable them in settings →</a>
                    @endif
                </div>
            @else
                {{-- 30 days --}}
                <div>
                    <div class="mb-2 flex items-center justify-between text-xs">
                        <span class="font-medium text-gray-700">Last 30 days</span>
                        @if($uptime['uptime_percent_30d'] !== null)
                            <span class="font-semibold text-gray-700 tabular-nums">{{ number_format($uptime['uptime_percent_30d'], 2) }}% uptime</span>
                        @endif
                    </div>
                    <div class="flex gap-[3px]">
                        @foreach($uptime['series_30d'] as $day)
                            @php
                                $u = $day['uptime_percent'];
                                $dc = match (true) {
                                    $day['checks'] === 0 => 'bg-gray-200',
                                    $u === null || $u < 90 => 'bg-red-500',
                                    $u < 95 => 'bg-orange-400',
                                    $u < 99.9 => 'bg-amber-400',
                                    default => 'bg-emerald-500',
                                };
                                $dt = $day['label'].' — '.($day['checks'] > 0
                                    ? ($u !== null ? $u.'% up' : 'all failed').($day['avg_ms'] !== null ? ', '.$day['avg_ms'].' ms avg' : '').', '.$day['checks'].' checks'
                                    : 'no data');
                            @endphp
                            <div class="h-9 flex-1 rounded-[3px] {{ $dc }} transition hover:opacity-75" title="{{ $dt }}"></div>
                        @endforeach
                    </div>
                    <div class="mt-1.5 flex justify-between text-[10px] text-gray-400">
                        <span>30 days ago</span>
                        <span>Today</span>
                    </div>
                </div>

                {{-- 3 hours --}}
                <div class="mt-6 border-t border-gray-100 pt-5">
                    <div class="mb-2 flex items-center justify-between text-xs">
                        <span class="font-medium text-gray-700">Response time, last 3 hours</span>
                        <span class="text-gray-400">5-minute buckets</span>
                    </div>
                    <div class="flex items-end gap-[2px]" style="height: 80px">
                        @foreach($uptime['series'] as $point)
                            @php
                                $maxMs = (int) ($uptime['max_avg_ms'] ?? 1);
                                $barH = match (true) {
                                    $point['checks'] === 0 => 2,
                                    $point['avg_ms'] === null => 76,
                                    default => (int) max(3, round($point['avg_ms'] / max(1, $maxMs) * 76)),
                                };
                                $barClass = $point['checks'] === 0 ? 'bg-gray-200' : ($point['avg_ms'] === null ? 'bg-red-400' : ($point['uptime_percent'] < 100 ? 'bg-amber-400' : 'bg-indigo-500'));
                                $tooltip = $point['label'].' — '.($point['checks']
                                    ? $point['checks'].' check(s)'.($point['avg_ms'] !== null ? ', '.$point['avg_ms'].' ms' : '').($point['uptime_percent'] !== null ? ', '.$point['uptime_percent'].'% up' : '')
                                    : 'no data');
                            @endphp
                            <div class="flex h-full flex-1 flex-col justify-end" title="{{ $tooltip }}">
                                <div class="{{ $barClass }} w-full rounded-t-sm transition hover:opacity-75" style="height: {{ $barH }}px"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-1 flex justify-between text-[10px] text-gray-400 tabular-nums">
                        @foreach($uptime['series'] as $point)
                            @if($loop->first || $loop->last || ($loop->iteration - 1) % 6 === 0)
                                <span>{{ $point['label'] }}</span>
                            @endif
                        @endforeach
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-gray-500">
                        <span class="inline-flex items-center gap-1.5"><span class="size-2 rounded-sm bg-indigo-500"></span> Avg response</span>
                        <span class="inline-flex items-center gap-1.5"><span class="size-2 rounded-sm bg-amber-400"></span> Some checks failed</span>
                        <span class="inline-flex items-center gap-1.5"><span class="size-2 rounded-sm bg-red-400"></span> All checks failed</span>
                        <span class="inline-flex items-center gap-1.5"><span class="size-2 rounded-sm bg-gray-200"></span> No data</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="card p-5">
            <h2 class="card-title mb-4">Project</h2>
            <dl class="space-y-3 text-xs">
                <div>
                    <dt class="mb-1 text-gray-500">Project key</dt>
                    <dd class="flex items-center gap-2">
                        <code class="min-w-0 flex-1 truncate rounded-md bg-gray-100 px-2 py-1 font-mono text-gray-800">{{ $project->key }}</code>
                        <x-copy-button :value="$project->key" />
                    </dd>
                </div>
                @if($project->description)
                    <div>
                        <dt class="mb-1 text-gray-500">Description</dt>
                        <dd class="text-gray-700">{{ $project->description }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">Total exceptions</dt>
                    <dd class="font-medium text-gray-900 tabular-nums">{{ number_format($project->total_exceptions) }}</dd>
                </div>
                @if($project->group)
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Group</dt>
                        <dd class="font-medium text-gray-900">
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('panel.projects.index', ['group_id' => $project->group_id]) }}" class="hover:text-indigo-600">{{ $project->group->title }}</a>
                            @else
                                {{ $project->group->title }}
                            @endif
                        </dd>
                    </div>
                @endif
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">Issue prefixes</dt>
                    <dd class="font-mono font-medium text-gray-900">{{ $project->group?->issue_prefix ? strtoupper($project->group->issue_prefix) : 'BUG' }} · OUT</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">Created</dt>
                    <dd class="text-gray-900">{{ $project->created_at->toFormattedDateString() }}</dd>
                </div>
            </dl>
            <div class="mt-4 border-t border-gray-100 pt-4">
                <p class="mb-2 text-xs text-gray-500">Last 14 days</p>
                <x-sparkline :series="$activity" :height="36" />
            </div>
        </div>

        <div class="card p-5" x-data="{ all: false }">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="card-title">Team</h2>
                <span class="text-xs text-gray-400">{{ $teamMembers->count() }} {{ Str::plural('member', $teamMembers->count()) }}</span>
            </div>
            <ul class="space-y-3">
                @foreach($teamMembers as $member)
                    <li class="flex items-center gap-3" @if($loop->index >= 5) x-show="all" x-cloak @endif>
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">
                            {{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-medium text-gray-900">{{ $member->name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ $member->email }}</p>
                        </div>
                        @if($member->is_project_owner)
                            <x-badge variant="indigo">Owner</x-badge>
                        @elseif($member->isAdmin())
                            <x-badge variant="gray">Admin</x-badge>
                        @elseif($member->has_group_access)
                            <x-badge variant="gray">Via group</x-badge>
                        @endif
                    </li>
                @endforeach
            </ul>
            @if($teamMembers->count() > 5)
                <button type="button" @click="all = ! all" class="mt-4 text-xs font-medium text-indigo-600 hover:text-indigo-800"
                        x-text="all ? 'Show less' : 'Show {{ $teamMembers->count() - 5 }} more'"></button>
            @endif
        </div>
    </div>
</div>
@endsection
