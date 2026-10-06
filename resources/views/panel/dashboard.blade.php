@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<x-page-header title="Dashboard" :description="'Welcome back, ' . auth()->user()->name . '. Here is what needs your attention.'" />

<div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
    <x-stat label="Projects" :value="$stats['total_projects']" :href="route('panel.projects.index')"
            :hint="$stats['offline_projects'] ? $stats['offline_projects'].' offline right now' : 'All monitored projects online'" />
    <x-stat label="Open exceptions" :value="number_format($stats['open_exceptions'])" :tone="$stats['open_exceptions'] ? 'danger' : 'success'"
            hint="New and not yet looked at" />
    <x-stat label="Offline now" :value="$stats['offline_projects']" :tone="$stats['offline_projects'] ? 'danger' : 'success'"
            hint="Uptime checks failing" />
    <x-stat label="Last 24 hours" :value="number_format($stats['last_24h'])" hint="Exceptions received" />
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        {{-- Activity --}}
        <div class="card p-5">
            <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
                <div>
                    <h2 class="card-title">Activity</h2>
                    <p class="text-xs text-gray-500">Exceptions received per day, last 14 days</p>
                </div>
                <div class="flex items-center gap-4 text-[11px] text-gray-500">
                    <span class="inline-flex items-center gap-1.5"><span class="size-2 rounded-sm bg-indigo-500"></span> Exceptions</span>
                    <span class="inline-flex items-center gap-1.5"><span class="size-2 rounded-sm bg-red-400"></span> Outages</span>
                </div>
            </div>
            @php $maxDay = max(1, $activity->max('total')); @endphp
            <div class="flex h-36 items-end gap-1.5 sm:gap-2">
                @foreach($activity as $day)
                    @php
                        $height = $day['total'] ? max(6, (int) round($day['total'] / $maxDay * 132)) : 3;
                        $outageHeight = $day['total'] ? (int) round($day['outages'] / max(1, $day['total']) * $height) : 0;
                    @endphp
                    <div class="group relative flex h-full flex-1 flex-col justify-end">
                        <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-[11px] text-white opacity-0 shadow transition group-hover:opacity-100">
                            {{ $day['label'] }} · {{ $day['total'] }} {{ Str::plural('exception', $day['total']) }}@if($day['outages']), {{ $day['outages'] }} {{ Str::plural('outage', $day['outages']) }}@endif
                        </div>
                        <div class="flex w-full flex-col overflow-hidden rounded-t {{ $day['total'] ? '' : 'bg-gray-200' }}" style="height: {{ $height }}px">
                            @if($outageHeight)
                                <div class="w-full bg-red-400" style="height: {{ $outageHeight }}px"></div>
                            @endif
                            @if($day['total'])
                                <div class="w-full flex-1 bg-indigo-500 transition group-hover:bg-indigo-600"></div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-1.5 flex gap-1.5 text-center text-[10px] text-gray-400 tabular-nums sm:gap-2">
                @foreach($activity as $day)
                    <span class="flex-1">{{ $day['short'] }}</span>
                @endforeach
            </div>
        </div>

        {{-- Latest exceptions --}}
        <div class="card overflow-hidden">
            <div class="card-header">
                <h2 class="card-title">Latest exceptions</h2>
                <span class="text-xs text-gray-400">Across all your projects</span>
            </div>
            @if($recentExceptions->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-2xl">🎉</p>
                    <p class="mt-2 text-sm text-gray-500">No exceptions yet.</p>
                </div>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach($recentExceptions as $ex)
                        <li>
                            <a href="{{ route('panel.projects.exceptions.show', [$ex->project_id, $ex->id]) }}" class="flex items-start gap-4 px-5 py-3.5 transition-colors hover:bg-gray-50">
                                <span class="mt-0.5 w-20 shrink-0 font-mono text-xs font-medium text-indigo-600">{{ $ex->issue_code ?? '—' }}</span>
                                <span class="min-w-0 flex-1">
                                    <x-exception-name :name="$ex->exception" class="block truncate text-sm" />
                                    <span class="mt-0.5 block truncate text-xs text-gray-500">{{ $ex->error }}</span>
                                </span>
                                <span class="hidden w-36 shrink-0 truncate text-xs text-gray-500 sm:block">{{ $ex->project?->title }}</span>
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
    </div>

    {{-- Needs attention --}}
    <div class="space-y-6">
        <div class="card overflow-hidden">
            <div class="card-header">
                <h2 class="card-title">Needs attention</h2>
                <span class="text-xs text-gray-400">{{ $attention->count() }} of {{ $projects->count() }}</span>
            </div>
            @if($attention->isEmpty())
                <div class="px-5 py-10 text-center">
                    <p class="text-sm font-medium text-gray-900">All clear</p>
                    <p class="mt-1 text-xs text-gray-500">Every project is online with no unresolved exceptions.</p>
                </div>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach($attention as $p)
                        <li>
                            <a href="{{ $p->is_offline ? route('panel.projects.show', $p) : route('panel.projects.exceptions.index', [$p, 'status' => $p->open_count ? 'OPEN' : 'READ']) }}"
                               class="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-gray-50">
                                <x-health-dot :status="$p->is_offline ? 'offline' : 'warning'" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-gray-900">{{ $p->title }}</span>
                                    <span class="block truncate text-xs text-gray-500">
                                        @if($p->is_offline)
                                            <span class="font-medium text-red-600">Offline</span> ·
                                        @endif
                                        {{ $p->unresolved_count }} unresolved
                                        @if($p->last_error_at) · last {{ $p->last_error_at->diffForHumans(short: true) }} @endif
                                    </span>
                                </span>
                                @if($p->open_count)
                                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-700 tabular-nums">{{ $p->open_count }} open</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="card p-5">
            <h2 class="card-title">Quick tips</h2>
            <ul class="mt-3 space-y-2.5 text-xs text-gray-600">
                <li class="flex items-center gap-2"><span class="kbd">⌘K</span> jump to any project or issue code</li>
                <li class="flex items-center gap-2"><span class="kbd">/</span> focus the search on exception lists</li>
                <li class="flex items-center gap-2"><span class="kbd">J</span><span class="kbd">K</span> older / newer exception while triaging</li>
            </ul>
        </div>
    </div>
</div>
@endsection
