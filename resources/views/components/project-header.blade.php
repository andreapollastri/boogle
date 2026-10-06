@props(['project', 'active' => 'overview', 'crumb' => null])

@php
$isAdmin = auth()->user()->isAdmin();
$health = $project->healthStatus();
$openCount = $project->exceptions()->where('status', \App\Models\ExceptionRecord::STATUS_OPEN)->count();
$host = $project->url ? (parse_url($project->url, PHP_URL_HOST) ?: $project->url) : null;

$tabs = array_filter([
    'overview' => ['Overview', route('panel.projects.show', $project), null],
    'exceptions' => ['Exceptions', route('panel.projects.exceptions.index', $project), $openCount ?: null],
    'installation' => $isAdmin ? ['Installation', route('panel.projects.installation', $project), null] : null,
    'settings' => $isAdmin ? ['Settings', route('panel.projects.edit', $project), null] : null,
]);
@endphp

<div class="mb-6">
    <nav class="mb-3 flex items-center gap-1.5 text-xs text-gray-500" aria-label="Breadcrumb">
        <a href="{{ route('panel.projects.index') }}" class="hover:text-gray-800">Projects</a>
        @if($isAdmin && $project->group)
            <svg class="size-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('panel.projects.index', ['group_id' => $project->group_id]) }}" class="hover:text-gray-800">{{ $project->group->title }}</a>
        @endif
        <svg class="size-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        @if($crumb)
            <a href="{{ $tabs[$active][1] ?? route('panel.projects.show', $project) }}" class="hover:text-gray-800">{{ $project->title }}</a>
            <svg class="size-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            <span class="font-mono text-gray-700">{{ $crumb }}</span>
        @else
            <span class="text-gray-700">{{ $project->title }}</span>
        @endif
    </nav>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex min-w-0 items-center gap-3.5">
            <div class="relative flex size-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-base font-bold text-white shadow-sm shadow-indigo-500/30">
                {{ mb_strtoupper(mb_substr($project->title, 0, 1)) }}
                <x-health-dot :status="$health" class="absolute -right-0.5 -bottom-0.5 !ring-2 !ring-white" />
            </div>
            <div class="min-w-0">
                <h1 class="truncate text-xl font-semibold tracking-tight text-gray-900">{{ $project->title }}</h1>
                <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500">
                    @if($host)
                        <a href="{{ $project->url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 hover:text-indigo-600">
                            {{ $host }}
                            <svg class="size-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    @endif
                    <x-health-badge :status="$health" />
                </div>
            </div>
        </div>
        @if(isset($actions))
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endif
    </div>

    <div class="mt-5 border-b border-gray-200">
        <nav class="-mb-px flex gap-1 overflow-x-auto" aria-label="Project sections">
            @foreach($tabs as $key => [$label, $href, $badge])
                <a href="{{ $href }}" @if($active === $key) aria-current="page" @endif
                   class="inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition-colors
                          {{ $active === $key ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-800' }}">
                    {{ $label }}
                    @if($badge)
                        <span class="rounded-full bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold leading-none text-red-700 tabular-nums">{{ $badge }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>
</div>
