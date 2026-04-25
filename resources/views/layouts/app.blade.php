<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') — Boogle</title>
    <link rel="icon" href="{{ 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y="0.9em" font-size="85" font-family="system-ui,Apple Color Emoji,Segoe UI Emoji,Noto Color Emoji,sans-serif">👾</text></svg>') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased" x-data="{ sidebarOpen: false }">

<div class="min-h-screen flex items-start">

    {{-- Sidebar desktop: viewport height, sticky so footer stays visible while main scrolls --}}
    <aside class="hidden lg:flex lg:flex-col lg:sticky lg:top-0 lg:h-screen lg:max-h-screen w-64 bg-white border-r border-gray-200 shrink-0">
        <div class="flex h-16 shrink-0 items-center gap-3 px-6 border-b border-gray-200">
            <span class="flex size-8 items-center justify-center rounded-lg bg-indigo-100 text-[1.25rem] leading-none select-none" role="img" aria-label="Boogle">👾</span>
            <span class="text-lg font-bold text-gray-900">Boogle</span>
        </div>

        <nav class="flex-1 min-h-0 px-3 py-5 space-y-0.5 overflow-y-auto">
            <x-nav-link href="{{ route('panel.dashboard') }}" :active="request()->routeIs('panel.dashboard')">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
                Dashboard
            </x-nav-link>
            <x-nav-link href="{{ route('panel.projects.index') }}" :active="request()->routeIs('panel.projects.*')">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h3l2 2h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                Projects
            </x-nav-link>
            @if(auth()->user()?->isAdmin())
                <x-nav-link href="{{ route('panel.groups.index') }}" :active="request()->routeIs('panel.groups.*')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Groups
                </x-nav-link>
            @endif
            @if(auth()->user()?->isAdmin())
                <x-nav-link href="{{ route('panel.users.index') }}" :active="request()->routeIs('panel.users.*')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A7 7 0 0112 15a7 7 0 016.879 2.804M15 11a3 3 0 11-6 0 3 3 0 016 0zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Users
                </x-nav-link>
            @endif
        </nav>

        <div class="shrink-0 px-3 py-4 border-t border-gray-200 space-y-0.5 bg-white">
            <x-nav-link href="{{ route('panel.profile') }}" :active="request()->routeIs('panel.profile*')">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Profile
            </x-nav-link>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Sign out
                </button>
            </form>
        </div>
    </aside>

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen" x-transition.opacity class="lg:hidden fixed inset-0 z-40 bg-black/40" @click="sidebarOpen=false"></div>

    {{-- Mobile sidebar --}}
    <div x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
         class="lg:hidden fixed inset-y-0 left-0 z-50 flex h-[100dvh] max-h-[100dvh] w-64 min-h-0 flex-col overflow-hidden border-r border-gray-200 bg-white">
        <div class="flex h-16 shrink-0 items-center justify-between px-6 border-b border-gray-200">
            <span class="inline-flex items-center gap-2">
                <span class="text-[1.25rem] leading-none select-none" role="img" aria-label="Boogle">👾</span>
                <span class="text-lg font-bold text-gray-900">Boogle</span>
            </span>
            <button @click="sidebarOpen=false" class="p-1 text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <nav class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 py-5 space-y-0.5">
            <x-nav-link href="{{ route('panel.dashboard') }}" :active="request()->routeIs('panel.dashboard')">Dashboard</x-nav-link>
            <x-nav-link href="{{ route('panel.projects.index') }}" :active="request()->routeIs('panel.projects.*')">Projects</x-nav-link>
            @if(auth()->user()?->isAdmin())
                <x-nav-link href="{{ route('panel.groups.index') }}" :active="request()->routeIs('panel.groups.*')">Groups</x-nav-link>
            @endif
            @if(auth()->user()?->isAdmin())
                <x-nav-link href="{{ route('panel.users.index') }}" :active="request()->routeIs('panel.users.*')">Users</x-nav-link>
            @endif
        </nav>
        <div class="shrink-0 space-y-0.5 border-t border-gray-200 bg-white px-3 pt-4 pb-[max(1rem,env(safe-area-inset-bottom,0px))]">
            <x-nav-link href="{{ route('panel.profile') }}" :active="request()->routeIs('panel.profile*')">Profile</x-nav-link>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-900">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Sign out
                </button>
            </form>
        </div>
    </div>

    {{-- Main content --}}
    <div class="flex-1 flex flex-col min-w-0">

        {{-- Mobile top bar --}}
        <div class="lg:hidden flex h-16 items-center gap-4 border-b border-gray-200 bg-white px-4 sticky top-0 z-30">
            <button @click="sidebarOpen=true" class="p-1 text-gray-500 hover:text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <span class="inline-flex items-center gap-2">
                <span class="text-xl leading-none select-none" role="img" aria-label="Boogle">👾</span>
                <span class="text-base font-bold text-gray-900">Boogle</span>
            </span>
        </div>

        <main class="flex-1 p-6">
            {{-- Flash --}}
            @if(session('success'))
                <div class="mb-5 flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-5 flex items-center gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
                    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

</body>
</html>
