@extends('layouts.app')
@section('title', $term !== '' ? 'Search: '.$term : 'Search')

@section('content')
<x-page-header title="Search" description="Find exceptions across every project you can access." />

<form method="GET" action="{{ route('panel.search') }}" class="mb-6 max-w-2xl">
    <div class="relative">
        <svg class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="search" name="q" value="{{ $term }}" data-shortcut="search" autofocus
               placeholder="Issue code (#SHOP42), exception class, message, file or URL…"
               class="field py-3 pr-4 pl-11 text-base">
    </div>
</form>

@if($term === '')
    <div class="card max-w-2xl p-6 text-sm text-gray-600">
        <p class="font-medium text-gray-900">Tips</p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
            <li>An issue code like <span class="font-mono">#OUT3</span> jumps straight to the exception when it is unique.</li>
            <li>Search by class (<span class="font-mono">QueryException</span>), message (<span class="font-mono">Deadlock</span>), file or URL.</li>
            <li>Press <span class="kbd">⌘K</span> anywhere to jump to a project.</li>
        </ul>
    </div>
@else
    @if($projects->isNotEmpty())
        <h2 class="mb-2 text-xs font-semibold tracking-wider text-gray-400 uppercase">Projects</h2>
        <div class="mb-6 flex flex-wrap gap-2">
            @foreach($projects as $p)
                <a href="{{ route('panel.projects.show', $p) }}" class="card inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-800 hover:border-indigo-300">
                    <x-health-dot :status="$p->healthStatus()" size="size-1.5" class="!ring-2" />
                    {{ $p->title }}
                    @if($p->open_count)
                        <span class="rounded-full bg-red-100 px-1.5 text-[10px] font-semibold text-red-700">{{ $p->open_count }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    @endif

    <h2 class="mb-2 text-xs font-semibold tracking-wider text-gray-400 uppercase">
        Exceptions <span class="ml-1 font-normal normal-case tracking-normal">{{ number_format($exceptions->total()) }} {{ Str::plural('result', $exceptions->total()) }}</span>
    </h2>
    <div class="card overflow-hidden">
        @if($exceptions->isEmpty())
            <p class="px-6 py-14 text-center text-sm text-gray-500">No exceptions match “{{ $term }}”.</p>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach($exceptions as $ex)
                    <li>
                        <a href="{{ route('panel.projects.exceptions.show', [$ex->project_id, $ex->id]) }}" class="flex items-start gap-4 px-5 py-3.5 transition-colors hover:bg-gray-50">
                            <span class="mt-0.5 w-20 shrink-0 font-mono text-xs font-medium text-indigo-600">{{ $ex->issue_code ?? '—' }}</span>
                            <span class="min-w-0 flex-1">
                                <x-exception-name :name="$ex->exception" :namespace="true" class="block truncate text-sm" />
                                <span class="mt-0.5 block truncate text-xs text-gray-500">{{ $ex->error }}</span>
                            </span>
                            <span class="hidden w-36 shrink-0 truncate text-xs text-gray-500 sm:block">{{ $ex->project?->title }}</span>
                            <span class="flex shrink-0 flex-col items-end gap-1">
                                <x-status-badge :status="$ex->status" />
                                <span class="text-[11px] text-gray-400">{{ $ex->created_at->diffForHumans(short: true) }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
            @if($exceptions->hasPages())
                <div class="border-t border-gray-100 px-4 py-3">{{ $exceptions->links() }}</div>
            @endif
        @endif
    </div>
@endif
@endsection
