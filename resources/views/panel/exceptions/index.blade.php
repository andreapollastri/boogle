@extends('layouts.app')
@section('title', 'Exceptions — ' . $project->title)

@php
$base = fn (array $overrides = []) => route('panel.projects.exceptions.index', array_merge(
    ['project' => $project],
    array_filter(array_merge($filters->toQuery(), $overrides), fn ($v) => $v !== null)
));
$statusTabs = ['ALL' => 'All', 'OPEN' => 'Open', 'READ' => 'Read', 'FIXED' => 'Fixed', 'DONE' => 'Done'];
@endphp

@section('content')
<x-project-header :project="$project" active="exceptions" />

<div class="card" x-data="exceptionList(@js($exceptions->pluck('id')->values()))">

    {{-- Status tabs --}}
    <div class="flex items-center gap-1 overflow-x-auto border-b border-gray-200 px-3 pt-2">
        @foreach($statusTabs as $key => $label)
            @php $isActive = $filters->status === $key; @endphp
            <a href="{{ $base(['status' => $key === 'ALL' ? null : $key, 'page' => null]) }}"
               class="-mb-px flex items-center gap-1.5 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition-colors
                      {{ $isActive ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                {{ $label }}
                <span class="rounded-full px-1.5 py-0.5 text-[11px] tabular-nums {{ $isActive ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500' }}">{{ number_format($counts[$key]) }}</span>
            </a>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('panel.projects.exceptions.index', $project) }}" class="space-y-3 border-b border-gray-100 px-4 py-3" x-ref="filters">
        @if($filters->status !== 'ALL')
            <input type="hidden" name="status" value="{{ $filters->status }}">
        @endif
        @if($filters->fingerprint)
            <input type="hidden" name="fingerprint" value="{{ $filters->fingerprint }}">
        @endif
        @if($errors->any())
            <div class="rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-xs text-red-600">{{ $errors->first() }}</div>
        @endif

        <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" name="search" value="{{ $filters->search }}" data-shortcut="search"
                       placeholder="Search issue code (#BUG12), class, message, file, URL or host…"
                       class="field pl-9 pr-10">
                <span class="kbd pointer-events-none absolute top-1/2 right-2.5 -translate-y-1/2">/</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <select name="kind" class="field w-auto py-2 pr-8" @change="$refs.filters.requestSubmit()" aria-label="Type">
                    <option value="all" @selected($filters->kind === 'all')>All types</option>
                    <option value="bug" @selected($filters->kind === 'bug')>Bugs (#BUG…)</option>
                    <option value="group" @selected($filters->kind === 'group')>Group prefix</option>
                    <option value="outage" @selected($filters->kind === 'outage')>Outages (#OUT…)</option>
                </select>
                <input type="date" name="date_from" value="{{ $filters->dateFrom }}" class="field w-auto py-2" aria-label="From date" @change="$refs.filters.requestSubmit()">
                <span class="text-xs text-gray-400">→</span>
                <input type="date" name="date_to" value="{{ $filters->dateTo }}" class="field w-auto py-2" aria-label="To date" @change="$refs.filters.requestSubmit()">
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50"
                       title="Show only the latest occurrence of each distinct error">
                    <input type="checkbox" name="grouped" value="1" @checked($filters->grouped) @change="$refs.filters.requestSubmit()">
                    Latest per error
                </label>
                <x-btn type="submit" variant="secondary" class="lg:hidden">Apply</x-btn>
            </div>
        </div>

        @if($filters->isNarrowed())
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="text-gray-500">Filtered by</span>
                @if($filters->search)
                    <a href="{{ $base(['search' => null, 'page' => null]) }}" class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-1 font-medium text-indigo-700 hover:bg-indigo-100">“{{ Str::limit($filters->search, 40) }}” <span aria-hidden="true">×</span></a>
                @endif
                @if($filters->kind !== 'all')
                    <a href="{{ $base(['kind' => null, 'page' => null]) }}" class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-1 font-medium text-indigo-700 hover:bg-indigo-100">{{ ['bug' => 'Bugs', 'group' => 'Group prefix', 'outage' => 'Outages'][$filters->kind] }} <span aria-hidden="true">×</span></a>
                @endif
                @if($filters->dateFrom || $filters->dateTo)
                    <a href="{{ $base(['date_from' => null, 'date_to' => null, 'page' => null]) }}" class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-1 font-medium text-indigo-700 hover:bg-indigo-100">{{ $filters->dateFrom ?? '…' }} → {{ $filters->dateTo ?? 'today' }} <span aria-hidden="true">×</span></a>
                @endif
                @if($filters->grouped)
                    <a href="{{ $base(['grouped' => null, 'page' => null]) }}" class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-1 font-medium text-indigo-700 hover:bg-indigo-100">Latest per error <span aria-hidden="true">×</span></a>
                @endif
                @if($filters->fingerprint)
                    <a href="{{ $base(['fingerprint' => null, 'page' => null]) }}" class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-1 font-medium text-indigo-700 hover:bg-indigo-100">
                        Same error as {{ $sameAs?->issue_code ?? 'selected' }} <span aria-hidden="true">×</span>
                    </a>
                @endif
                <a href="{{ route('panel.projects.exceptions.index', array_filter(['project' => $project, 'status' => $filters->status !== 'ALL' ? $filters->status : null])) }}" class="ml-1 font-medium text-gray-500 hover:text-gray-800">Clear all</a>
            </div>
        @endif
    </form>

    {{-- Bulk bar --}}
    <div x-show="selected.length > 0" x-cloak x-transition.opacity class="sticky top-14 z-20 flex flex-wrap items-center gap-3 border-b border-indigo-100 bg-indigo-50 px-4 py-2.5 lg:top-0">
        <span class="text-sm font-medium text-indigo-800" x-text="selected.length + ' selected'"></span>
        <button type="button" @click="selected = []" class="text-xs text-indigo-600 hover:text-indigo-800">Clear</button>
        <form method="POST" action="{{ route('panel.projects.exceptions.bulk', $project) }}" x-ref="bulkForm" class="ml-auto flex flex-wrap items-center gap-1.5">
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
            <input type="hidden" name="action" x-ref="bulkAction">
            <span class="mr-1 text-xs text-indigo-700">Mark as</span>
            @foreach(['READ' => 'Read', 'FIXED' => 'Fixed', 'DONE' => 'Done', 'OPEN' => 'Reopen'] as $action => $label)
                <button type="button" @click="bulk('{{ $action }}')" class="rounded-md border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">{{ $label }}</button>
            @endforeach
            <button type="button" @click="bulk('delete')" class="ml-1 rounded-md border border-red-200 bg-white px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50">Delete</button>
        </form>
    </div>

    {{-- List --}}
    @if($exceptions->isEmpty())
        <div class="py-16 text-center">
            <svg class="mx-auto mb-3 size-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm font-medium text-gray-900">{{ $filters->isNarrowed() || $filters->status !== 'ALL' ? 'No exceptions match these filters' : 'No exceptions yet' }}</p>
            <p class="mt-1 text-xs text-gray-500">
                @if($filters->isNarrowed() || $filters->status !== 'ALL')
                    <a href="{{ route('panel.projects.exceptions.index', $project) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Show everything</a>
                @elseif(auth()->user()->isAdmin())
                    Wire up the client from the <a href="{{ route('panel.projects.installation', $project) }}" class="font-medium text-indigo-600 hover:text-indigo-800">installation guide</a>.
                @endif
            </p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50/80 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="w-px py-2.5 pl-4">
                            <input type="checkbox" :checked="allSelected" @click="toggleAll()" aria-label="Select all on this page">
                        </th>
                        <th class="w-px px-3 py-2.5 whitespace-nowrap">Issue</th>
                        <th class="px-3 py-2.5">Exception</th>
                        <th class="hidden w-px px-3 py-2.5 text-right whitespace-nowrap md:table-cell">Seen</th>
                        <th class="w-px px-3 py-2.5 whitespace-nowrap">Status</th>
                        <th class="w-px py-2.5 pr-4 text-right whitespace-nowrap">When</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($exceptions as $ex)
                        @php
                            $url = route('panel.projects.exceptions.show', [$project, $ex]);
                            $where = $ex->full_url
                                ? trim(($ex->method ? $ex->method.' ' : '').(parse_url($ex->full_url, PHP_URL_PATH) ?: '/').(parse_url($ex->full_url, PHP_URL_QUERY) ? '?'.parse_url($ex->full_url, PHP_URL_QUERY) : ''))
                                : ($ex->file ? Str::afterLast($ex->file, '/').($ex->line ? ':'.$ex->line : '') : null);
                            $times = (int) ($seen[$ex->fingerprint] ?? 1);
                        @endphp
                        <tr class="cursor-pointer transition-colors hover:bg-gray-50" :class="selected.includes(@js($ex->id)) && 'bg-indigo-50/60'" @click="openRow($event, @js($url))">
                            <td class="py-3 pl-4 align-top">
                                <input type="checkbox" value="{{ $ex->id }}" x-model="selected" class="mt-0.5" aria-label="Select {{ $ex->issue_code }}">
                            </td>
                            <td class="px-3 py-3 align-top whitespace-nowrap">
                                <a href="{{ $url }}" class="font-mono text-xs font-medium text-indigo-600 hover:underline">{{ $ex->issue_code ?? '—' }}</a>
                            </td>
                            <td class="max-w-0 px-3 py-3 align-top">
                                <div class="flex min-w-0 items-center gap-2">
                                    @if($ex->status === 'OPEN')
                                        <span class="size-1.5 shrink-0 rounded-full bg-red-500" title="Unread"></span>
                                    @endif
                                    <x-exception-name :name="$ex->exception" :namespace="true" class="truncate text-sm" />
                                </div>
                                <p class="mt-0.5 line-clamp-1 text-xs text-gray-600" title="{{ $ex->error }}">{{ $ex->error ?: '—' }}</p>
                                @if($where)
                                    <p class="mt-1 truncate font-mono text-[11px] text-gray-400" title="{{ $ex->full_url ?: $ex->file }}">{{ $where }}</p>
                                @endif
                            </td>
                            <td class="hidden px-3 py-3 text-right align-top md:table-cell">
                                @if($times > 1)
                                    <a href="{{ route('panel.projects.exceptions.index', [$project, 'fingerprint' => $ex->fingerprint]) }}"
                                       class="inline-flex items-center rounded-md bg-gray-100 px-1.5 py-0.5 text-[11px] font-semibold text-gray-600 tabular-nums hover:bg-indigo-100 hover:text-indigo-700"
                                       title="This error was reported {{ $times }} times — show all">×{{ $times }}</a>
                                @else
                                    <span class="text-[11px] text-gray-300">×1</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 align-top whitespace-nowrap">
                                <x-status-badge :status="$ex->status" />
                            </td>
                            <td class="py-3 pr-4 text-right align-top text-xs whitespace-nowrap text-gray-500" title="{{ $ex->created_at->toDayDateTimeString() }}">
                                {{ $ex->created_at->diffForHumans(short: true) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-2 border-t border-gray-100 px-4 py-3 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between">
            <span>Showing {{ $exceptions->firstItem() }}–{{ $exceptions->lastItem() }} of {{ number_format($exceptions->total()) }}</span>
            @if($exceptions->hasPages())
                <div>{{ $exceptions->onEachSide(1)->links() }}</div>
            @endif
        </div>
    @endif
</div>
@endsection
