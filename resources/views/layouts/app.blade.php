<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') — Boogle</title>
    <link rel="icon" href="{{ 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y="0.9em" font-size="85" font-family="system-ui,Apple Color Emoji,Segoe UI Emoji,Noto Color Emoji,sans-serif">👾</text></svg>') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|jetbrains-mono:400,500&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 font-sans text-gray-900 antialiased" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">

<div class="min-h-screen lg:flex">

    {{-- Desktop sidebar --}}
    <aside class="hidden w-64 shrink-0 border-r border-gray-200 bg-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col">
        @include('layouts.partials.sidebar')
    </aside>

    {{-- Mobile drawer --}}
    <div x-show="sidebarOpen" x-transition.opacity x-cloak class="fixed inset-0 z-40 bg-gray-900/40 lg:hidden" @click="sidebarOpen = false"></div>
    <aside x-show="sidebarOpen" x-cloak
           x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
           class="fixed inset-y-0 left-0 z-50 flex h-[100dvh] w-72 flex-col border-r border-gray-200 bg-white shadow-xl lg:hidden">
        @include('layouts.partials.sidebar', ['mobile' => true])
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        {{-- Mobile top bar --}}
        <div class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-gray-200 bg-white/90 px-4 backdrop-blur lg:hidden">
            <button type="button" @click="sidebarOpen = true" class="-ml-1 rounded-md p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900" aria-label="Open navigation">
                <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <a href="{{ route('panel.dashboard') }}" class="inline-flex items-center gap-2">
                <span class="text-xl leading-none select-none" role="img" aria-label="Boogle">👾</span>
                <span class="text-base font-bold">Boogle</span>
            </a>
            <button type="button" @click="$dispatch('open-palette')" class="ml-auto rounded-md p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900" aria-label="Search">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
        </div>

        <main class="mx-auto w-full max-w-[90rem] flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            @include('layouts.partials.flash')
            @yield('content')
        </main>
    </div>
</div>

@include('layouts.partials.command-palette')
@stack('scripts')
</body>
</html>
