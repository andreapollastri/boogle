@extends('layouts.app')
@section('title', 'Exceptions — ' . $project->title)

@section('content')
<x-page-header :title="$project->title" description="Exception log"
    :breadcrumbs="[['label'=>'Projects','href'=>route('panel.projects.index')],['label'=>$project->title,'href'=>route('panel.projects.show',$project)],['label'=>'Exceptions']]">
    <x-slot:actions>
        <x-btn href="{{ route('panel.projects.show', $project) }}" variant="secondary">Overview</x-btn>
        @if(auth()->user()->isAdmin())
            <x-btn href="{{ route('panel.projects.edit', $project) }}" variant="secondary">Settings</x-btn>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="bg-white rounded-2xl border border-gray-200 overflow-hidden" x-data="exceptionList()">

    {{-- Tabs --}}
    <div class="flex items-center border-b border-gray-200 px-4 pt-4 overflow-x-auto gap-1">
        @foreach(['ALL' => 'All', 'OPEN' => 'Open', 'READ' => 'Read', 'FIXED' => 'Fixed', 'DONE' => 'Done'] as $key => $label)
            @php
                $count = $key === 'ALL' ? $counts['all'] : $counts[strtolower($key)];
                $isActive = $status === $key;
            @endphp
            <a href="{{ route('panel.projects.exceptions.index', array_merge(['project' => $project], $indexQuery, ['status' => $key])) }}"
               class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium border-b-2 transition-colors whitespace-nowrap -mb-px
                      {{ $isActive ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ $label }}
                <span class="text-xs px-1.5 py-0.5 rounded-full {{ $isActive ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500' }}">{{ $count }}</span>
            </a>
        @endforeach
    </div>

    {{-- Search & filters --}}
    <div class="px-4 py-3 border-b border-gray-100 space-y-3">
        @if($errors->any())
            <div class="text-xs text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2">
                {{ $errors->first() }}
            </div>
        @endif
        <form method="GET" action="{{ route('panel.projects.exceptions.index', $project) }}" class="space-y-3">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cerca: codice (#OUT1), nome classe, messaggio, path, URL o host…"
                       class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-colors" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                <div>
                    <label for="ex-date-from" class="block text-xs font-medium text-gray-600 mb-1">Periodo — da</label>
                    <input type="date" name="date_from" id="ex-date-from" value="{{ $dateFrom }}"
                           class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" />
                </div>
                <div>
                    <label for="ex-date-to" class="block text-xs font-medium text-gray-600 mb-1">Periodo — a</label>
                    <input type="date" name="date_to" id="ex-date-to" value="{{ $dateTo }}"
                           class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" />
                </div>
                <div class="sm:col-span-2 lg:col-span-2">
                    <label for="ex-kind" class="block text-xs font-medium text-gray-600 mb-1">Tipologia</label>
                    <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                        <select name="kind" id="ex-kind" class="w-full sm:flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            <option value="all" {{ $kind === 'all' ? 'selected' : '' }}>Tutte</option>
                            <option value="outage" {{ $kind === 'outage' ? 'selected' : '' }}>Outage (uptime, #OUT…)</option>
                            <option value="bug" {{ $kind === 'bug' ? 'selected' : '' }}>Bug / #BUG…</option>
                            <option value="group" {{ $kind === 'group' ? 'selected' : '' }}>Bug di gruppo (prefisso personalizzato)</option>
                        </select>
                        <button type="submit" class="w-full sm:w-auto shrink-0 inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">Applica</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Bulk bar --}}
    <div x-show="selected.length > 0" x-cloak class="px-4 py-3 bg-indigo-50 border-b border-indigo-100 flex items-center gap-4">
        <span class="text-sm text-indigo-700 font-medium" x-text="selected.length + ' selected'"></span>
        <div class="flex items-center gap-2 ml-auto">
            <form method="POST" action="{{ route('panel.projects.exceptions.bulk', $project) }}" x-ref="bulkForm">
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <input type="hidden" name="action" x-ref="bulkAction">
                <button type="button" @click="bulk('FIXED')" class="text-xs px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">Mark FIXED</button>
                <button type="button" @click="bulk('DONE')" class="text-xs px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 ml-1">Mark DONE</button>
                <button type="button" @click="bulk('delete')" class="text-xs px-3 py-1.5 rounded-lg bg-red-50 border border-red-200 text-red-700 hover:bg-red-100 ml-1">Delete</button>
            </form>
        </div>
    </div>

    {{-- List --}}
    @if($exceptions->isEmpty())
        <div class="py-16 text-center">
            <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm text-gray-500">No exceptions found.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-gray-50 border-b border-gray-200 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="w-8 pl-4 py-2.5"></th>
                        <th class="px-3 py-2.5">Codice</th>
                        <th class="px-3 py-2.5">Nome</th>
                        <th class="px-3 py-2.5 min-w-[200px]">Descrizione errore</th>
                        <th class="px-3 py-2.5 min-w-[180px]">Path / URL</th>
                        <th class="px-3 py-2.5">Stato</th>
                        <th class="pr-4 py-2.5 text-right w-32">Quando</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($exceptions as $ex)
                        <tr class="hover:bg-gray-50 transition-colors {{ $ex->status === 'OPEN' ? 'bg-red-50/30' : '' }}">
                            <td class="pl-4 py-3 align-top">
                                <input type="checkbox" :value="'{{ $ex->id }}'" x-model="selected"
                                       class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @click.stop />
                            </td>
                            <td class="px-3 py-3 align-top font-mono text-indigo-600 whitespace-nowrap">
                                <a href="{{ route('panel.projects.exceptions.show', [$project, $ex]) }}" class="hover:underline">{{ $ex->issue_code ?? '—' }}</a>
                            </td>
                            <td class="px-3 py-3 align-top text-gray-900 min-w-0 max-w-xs">
                                <a href="{{ route('panel.projects.exceptions.show', [$project, $ex]) }}" class="block truncate hover:text-indigo-600" title="{{ $ex->exception }}">
                                    {{ $ex->exception ?: '—' }}
                                </a>
                            </td>
                            <td class="px-3 py-3 align-top text-gray-600 min-w-0 max-w-sm">
                                <span class="line-clamp-2" title="{{ $ex->error }}">{{ $ex->error ? Str::limit($ex->error, 200) : '—' }}</span>
                            </td>
                            <td class="px-3 py-3 align-top text-gray-500 min-w-0 text-xs break-all">
                                @if($ex->full_url)
                                    <span class="line-clamp-2" title="{{ $ex->full_url }}">{{ Str::limit($ex->full_url, 100) }}</span>
                                @elseif($ex->file)
                                    <span class="line-clamp-2" title="{{ $ex->file }}">{{ Str::limit($ex->file, 100) }}{{ $ex->line !== null ? ' :'.$ex->line : '' }}</span>
                                @else
                                    <span>—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 align-top whitespace-nowrap">
                                <a href="{{ route('panel.projects.exceptions.show', [$project, $ex]) }}">
                                    <x-status-badge :status="$ex->status" />
                                </a>
                            </td>
                            <td class="pr-4 py-3 align-top text-right text-xs text-gray-400 whitespace-nowrap">
                                <a href="{{ route('panel.projects.exceptions.show', [$project, $ex]) }}">{{ $ex->created_at->diffForHumans() }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($exceptions->hasPages())
            <div class="px-4 py-4 border-t border-gray-100 text-xs text-gray-500 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <span>Mostrando {{ $exceptions->firstItem() }}–{{ $exceptions->lastItem() }} di {{ $exceptions->total() }}</span>
                <div>{{ $exceptions->links() }}</div>
            </div>
        @endif
    @endif
</div>

<script>
function exceptionList() {
    return {
        selected: [],
        bulk(action) {
            if (action === 'delete' && !confirm('Delete selected exceptions?')) return;
            this.$refs.bulkAction.value = action;
            this.$refs.bulkForm.submit();
        }
    }
}
</script>
@endsection
