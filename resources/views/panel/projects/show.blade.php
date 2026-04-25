@extends('layouts.app')
@section('title', $project->title)

@section('content')
<x-page-header :title="$project->title" :description="$project->url ?: ''"
    :breadcrumbs="[['label'=>'Projects','href'=>route('panel.projects.index')],['label'=>$project->title]]">
    <x-slot:actions>
        @if(auth()->user()->isAdmin())
            <x-btn href="{{ route('panel.projects.installation', $project) }}" variant="secondary">Installation</x-btn>
            <x-btn href="{{ route('panel.projects.edit', $project) }}" variant="secondary">Settings</x-btn>
        @endif
        <x-btn href="{{ route('panel.projects.exceptions.index', $project) }}">View exceptions</x-btn>
    </x-slot:actions>
</x-page-header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Project info</h3>
            <dl class="space-y-3">
                <div class="flex gap-3">
                    <dt class="text-xs text-gray-500 w-32 shrink-0">Project key</dt>
                    <dd class="text-xs font-mono text-gray-900 bg-gray-100 px-2 py-1 rounded break-all">{{ $project->key }}</dd>
                </div>
                @if($project->description)
                    <div class="flex gap-3">
                        <dt class="text-xs text-gray-500 w-32 shrink-0">Description</dt>
                        <dd class="text-xs text-gray-700">{{ $project->description }}</dd>
                    </div>
                @endif
                <div class="flex gap-3">
                    <dt class="text-xs text-gray-500 w-32 shrink-0">Total exceptions</dt>
                    <dd class="text-xs text-gray-900">{{ number_format($project->total_exceptions) }}</dd>
                </div>
                @if(auth()->user()->isAdmin() && $project->group)
                    <div class="flex gap-3">
                        <dt class="text-xs text-gray-500 w-32 shrink-0">Group</dt>
                        <dd class="text-xs text-gray-900">
                            {{ $project->group->title }}
                            <span class="ml-2 text-[11px] text-gray-500">({{ $project->group->projects()->count() }} projects)</span>
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Uptime overview</h3>
                    <p class="text-xs text-gray-500 mt-1">Uptime and response times are computed from one HTTP check per minute (when enabled).</p>
                    @if($uptimeOverview['ping_url'])
                        <p class="text-xs text-gray-500 mt-1">Ping URL: <span class="font-mono">{{ $uptimeOverview['ping_url'] }}</span></p>
                    @endif
                </div>
                @if($uptimeOverview['status'] === 'offline')
                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-medium text-red-700">Offline now</span>
                @elseif($uptimeOverview['status'] === 'disabled')
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-medium text-gray-700">Uptime disabled</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-medium text-emerald-700">Online now</span>
                @endif
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                <div class="rounded-xl border border-gray-200 p-3">
                    <p class="text-[11px] text-gray-500">Uptime (30d)</p>
                    <p class="text-base font-semibold text-gray-900 mt-1">
                        @if($uptimeOverview['has_uptime_samples'] && $uptimeOverview['uptime_percent_30d'] !== null)
                            {{ number_format($uptimeOverview['uptime_percent_30d'], 1) }}%
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </p>
                    @if(! $uptimeOverview['has_uptime_samples'] && $project->uptime_enabled)
                        <p class="text-[10px] text-gray-400 mt-0.5">Visible after the first scheduled checks</p>
                    @endif
                </div>
                <div class="rounded-xl border border-gray-200 p-3">
                    <p class="text-[11px] text-gray-500">Offline incidents (30d)</p>
                    <p class="text-base font-semibold text-gray-900 mt-1">{{ $uptimeOverview['offline_incidents_30d'] }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-3">
                    <p class="text-[11px] text-gray-500">Last outage</p>
                    <p class="text-base font-semibold text-gray-900 mt-1">
                        {{ $uptimeOverview['last_outage_at'] ? \Illuminate\Support\Carbon::parse($uptimeOverview['last_outage_at'])->diffForHumans() : 'No outages yet' }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 p-3">
                    <p class="text-[11px] text-gray-500">Avg response time (30d)</p>
                    <p class="text-base font-semibold text-gray-900 mt-1">
                        @if($uptimeOverview['avg_response_ms_30d'] !== null)
                            {{ number_format($uptimeOverview['avg_response_ms_30d']) }} ms
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </p>
                    @if(! $uptimeOverview['has_uptime_samples'] && $project->uptime_enabled)
                        <p class="text-[10px] text-gray-400 mt-0.5">Visible after successful checks</p>
                    @endif
                </div>
            </div>

            <div>
                <p class="text-xs font-medium text-gray-700 mb-1">Response time (last 3 hours)</p>
                <p class="text-[11px] text-gray-500 mb-3">Each bar = 5-minute slot. Height = avg response time of successful checks. Hover for details.</p>
                @if(! $project->uptime_enabled)
                    <p class="text-xs text-gray-500">Uptime checks are off — enable them in project settings to collect data.</p>
                @else
                    <div class="flex items-end gap-px" style="height:80px;">
                        @foreach($uptimeOverview['series'] as $point)
                            @php
                                $maxMs = (int) ($uptimeOverview['max_avg_ms'] ?? 1);
                                if ($point['checks'] === 0) {
                                    $barH = 2;
                                } elseif ($point['avg_ms'] === null) {
                                    $barH = 76;
                                } else {
                                    $barH = (int) max(3, round($point['avg_ms'] / max(1, $maxMs) * 76));
                                }
                                $barClass = $point['checks'] === 0
                                    ? 'bg-gray-200'
                                    : ($point['avg_ms'] === null ? 'bg-red-400' : 'bg-indigo-500');
                                $tooltip = $point['label'];
                                if ($point['checks']) {
                                    $tooltip .= ' — ' . $point['checks'] . ' check(s)';
                                    if ($point['avg_ms'] !== null) $tooltip .= ', ' . $point['avg_ms'] . 'ms';
                                    if ($point['uptime_percent'] !== null) $tooltip .= ', ' . $point['uptime_percent'] . '% up';
                                } else {
                                    $tooltip .= ' — no data';
                                }
                            @endphp
                            <div class="flex-1 flex flex-col justify-end" style="height:80px;" title="{{ $tooltip }}">
                                <div class="{{ $barClass }} w-full rounded-t-sm" style="height:{{ $barH }}px;"></div>
                            </div>
                        @endforeach
                    </div>
                    {{-- Time axis: first, every 6 slots (+30min), last --}}
                    <div class="flex gap-px mt-1">
                        @foreach($uptimeOverview['series'] as $point)
                            <div class="flex-1 text-center">
                                @if($loop->first || $loop->last || ($loop->iteration - 1) % 6 === 0)
                                    <span class="text-[9px] text-gray-400 tabular-nums">{{ $point['label'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-gray-500">
                        <span class="inline-flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-sm bg-indigo-500"></span> Avg response (2xx/3xx)</span>
                        <span class="inline-flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-sm bg-red-400"></span> All checks failed</span>
                        <span class="inline-flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-sm bg-gray-200"></span> No data</span>
                    </div>

                    {{-- 30-day status chart --}}
                    <div class="mt-6 pt-5 border-t border-gray-100">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-medium text-gray-700">Uptime (last 30 days)</p>
                            @if($uptimeOverview['uptime_percent_30d'] !== null)
                                <span class="text-xs font-semibold text-gray-600">{{ $uptimeOverview['uptime_percent_30d'] }}% uptime</span>
                            @endif
                        </div>
                        <div class="flex gap-1">
                            @foreach($uptimeOverview['series_30d'] as $day)
                                @php
                                    $u = $day['uptime_percent'];
                                    if ($day['checks'] === 0) {
                                        $dc = 'bg-gray-200';
                                    } elseif ($u === null || $u < 90) {
                                        $dc = 'bg-red-500';
                                    } elseif ($u < 95) {
                                        $dc = 'bg-orange-400';
                                    } elseif ($u < 99) {
                                        $dc = 'bg-yellow-400';
                                    } else {
                                        $dc = 'bg-emerald-500';
                                    }
                                    $dt = $day['label'];
                                    if ($day['checks'] > 0) {
                                        $dt .= ' — ' . ($u !== null ? $u.'% up' : 'all failed');
                                        if ($day['avg_ms'] !== null) $dt .= ', '.$day['avg_ms'].'ms avg';
                                        $dt .= ', '.$day['checks'].' checks';
                                    } else {
                                        $dt .= ' — no data';
                                    }
                                @endphp
                                <div class="flex-1 h-8 rounded-sm {{ $dc }} cursor-default transition-opacity hover:opacity-80" title="{{ $dt }}"></div>
                            @endforeach
                        </div>
                        <div class="flex justify-between mt-1.5 text-[9px] text-gray-400">
                            <span>30d ago</span>
                            <span>Today</span>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-gray-500">
                            <span class="inline-flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-500"></span> 100%</span>
                            <span class="inline-flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-sm bg-yellow-400"></span> 95–99%</span>
                            <span class="inline-flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-sm bg-orange-400"></span> 90–95%</span>
                            <span class="inline-flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-sm bg-red-500"></span> &lt; 90%</span>
                            <span class="inline-flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-sm bg-gray-200"></span> No data</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>

    <div class="space-y-5">
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Team members</h3>
            @php($visibleTeamMembers = $teamMembers->take(5))
            <ul class="space-y-3">
                @foreach($visibleTeamMembers as $user)
                    <li class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-xs font-medium text-indigo-700">{{ strtoupper($user->name[0]) }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-gray-900 truncate">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                        </div>
                        @if($user->is_project_owner)
                            <x-badge variant="indigo">Owner</x-badge>
                        @endif
                        @if($user->isAdmin())
                            <x-badge variant="gray">Admin</x-badge>
                        @elseif($user->has_group_access)
                            <x-badge variant="gray">Group access</x-badge>
                        @endif
                    </li>
                @endforeach
            </ul>
            @if($teamMembers->count() > 5)
                <div x-data="{ openAllMembersModal: false }" class="mt-4">
                    <button type="button"
                            @click="openAllMembersModal = true"
                            class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                        + {{ $teamMembers->count() - 5 }} users
                    </button>

                    <div x-show="openAllMembersModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                        <div class="fixed inset-0 bg-black/40" @click="openAllMembersModal = false"></div>
                        <div class="relative bg-white rounded-2xl shadow-xl p-6 w-full max-w-2xl max-h-[80vh] overflow-auto">
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="text-base font-semibold text-gray-900">All team members</h3>
                                <button type="button" @click="openAllMembersModal = false" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <ul class="space-y-3">
                                @foreach($teamMembers as $user)
                                    <li class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                            <span class="text-xs font-medium text-indigo-700">{{ strtoupper($user->name[0]) }}</span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-medium text-gray-900 truncate">{{ $user->name }}</p>
                                            <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                                        </div>
                                        @if($user->is_project_owner)
                                            <x-badge variant="indigo">Owner</x-badge>
                                        @endif
                                        @if($user->isAdmin())
                                            <x-badge variant="gray">Admin</x-badge>
                                        @elseif($user->has_group_access)
                                            <x-badge variant="gray">Group access</x-badge>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if(auth()->user()->isAdmin())
            <div x-data="{ openDeleteProjectModal: false }" class="bg-white rounded-2xl border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-red-600 mb-3">Danger zone</h3>
                <button type="button"
                        @click="openDeleteProjectModal = true"
                        class="text-xs text-red-600 hover:text-red-700 font-medium">
                    Delete this project →
                </button>

                <div x-show="openDeleteProjectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/40" @click="openDeleteProjectModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 w-9 h-9 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">Delete project “{{ $project->title }}”?</h3>
                                <p class="mt-2 text-sm text-gray-600">
                                    This action is permanent. The project and all related data will be removed, including exceptions and project associations.
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-3">
                            <x-btn variant="secondary" type="button" @click="openDeleteProjectModal = false">Cancel</x-btn>
                            <form method="POST" action="{{ route('panel.projects.destroy', $project) }}">
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
        @endif
    </div>
</div>
@endsection
