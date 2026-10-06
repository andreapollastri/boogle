@php
$mobile = $mobile ?? false;
$user = auth()->user();
$routeProject = request()->route('project');
$currentProjectId = $routeProject instanceof \App\Models\Project ? $routeProject->id : $routeProject;
$sidebarProjects = $navProjects->take(8);
@endphp

<div class="flex h-16 shrink-0 items-center justify-between gap-3 px-5">
    <a href="{{ route('panel.dashboard') }}" class="inline-flex items-center gap-2.5">
        <span class="flex size-8 items-center justify-center rounded-lg bg-indigo-100 text-[1.2rem] leading-none select-none" role="img" aria-label="Boogle">👾</span>
        <span class="text-lg font-bold tracking-tight text-gray-900">Boogle</span>
    </a>
    @if($mobile)
        <button type="button" @click="sidebarOpen = false" class="rounded-md p-1 text-gray-400 hover:text-gray-600" aria-label="Close navigation">
            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    @endif
</div>

<div class="px-3 pb-2">
    <button type="button" @click="sidebarOpen = false; $dispatch('open-palette')"
            class="flex w-full items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 transition hover:border-gray-300 hover:bg-white">
        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <span class="flex-1 text-left">Search or jump to…</span>
        <span class="kbd">⌘K</span>
    </button>
</div>

<nav class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain px-3 py-3" aria-label="Main">
    <div class="space-y-0.5">
        <x-nav-link href="{{ route('panel.dashboard') }}" :active="request()->routeIs('panel.dashboard')">
            <svg class="size-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zm10-4a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z"/></svg>
            Dashboard
        </x-nav-link>
        <x-nav-link href="{{ route('panel.projects.index') }}" :active="request()->routeIs('panel.projects.index', 'panel.projects.create')">
            <svg class="size-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h3l2 2h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
            Projects
        </x-nav-link>
        @if($user->isAdmin())
            <x-nav-link href="{{ route('panel.groups.index') }}" :active="request()->routeIs('panel.groups.*')">
                <svg class="size-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Groups
            </x-nav-link>
            <x-nav-link href="{{ route('panel.users.index') }}" :active="request()->routeIs('panel.users.*')">
                <svg class="size-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Users
            </x-nav-link>
        @endif
    </div>

    @if($navProjects->isNotEmpty())
        <div>
            <div class="mb-1 flex items-center justify-between px-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Your projects</p>
                @if($user->isAdmin())
                    <a href="{{ route('panel.projects.create') }}" class="rounded p-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700" title="New project" aria-label="New project">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </a>
                @endif
            </div>
            <ul class="space-y-0.5">
                @foreach($sidebarProjects as $navProject)
                    @php $isCurrent = $currentProjectId === $navProject['id']; @endphp
                    <li>
                        <a href="{{ $navProject['url'] }}" @if($isCurrent) aria-current="page" @endif
                           class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-sm transition-colors {{ $isCurrent ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                            <x-health-dot :status="$navProject['health'] === 'offline' ? 'offline' : ($navProject['open'] > 0 ? 'warning' : $navProject['health'])" size="size-1.5" class="!ring-2" />
                            <span class="min-w-0 flex-1 truncate">{{ $navProject['title'] }}</span>
                            @if($navProject['open'] > 0)
                                <span class="rounded-full bg-red-50 px-1.5 text-[10px] font-semibold leading-4 text-red-600 tabular-nums">{{ $navProject['open'] }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
            @if($navProjects->count() > $sidebarProjects->count())
                <a href="{{ route('panel.projects.index') }}" class="mt-1 block px-3 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-700">
                    View all {{ $navProjects->count() }} projects →
                </a>
            @endif
        </div>
    @endif
</nav>

<div class="shrink-0 space-y-0.5 border-t border-gray-200 px-3 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom,0px))]">
    <a href="{{ route('panel.profile') }}"
       class="flex items-center gap-3 rounded-lg px-2 py-2 transition-colors {{ request()->routeIs('panel.profile*') ? 'bg-indigo-50' : 'hover:bg-gray-100' }}">
        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
        <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-medium text-gray-900">{{ $user->name }}</span>
            <span class="block truncate text-xs text-gray-500">{{ $user->email }}</span>
        </span>
    </a>
    <div class="flex items-center gap-1 px-1">
        <a href="{{ route('api.docs') }}" class="flex-1 rounded-md px-2 py-1.5 text-xs font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900">API docs</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-md px-2 py-1.5 text-xs font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Sign out
            </button>
        </form>
    </div>
</div>
