<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') — Boogle</title>
    <link rel="icon" href="{{ 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y="0.9em" font-size="85" font-family="system-ui,Apple Color Emoji,Segoe UI Emoji,Noto Color Emoji,sans-serif">👾</text></svg>') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-gradient-to-br from-indigo-950 via-indigo-900 to-slate-900 flex items-center justify-center p-4">

<div class="w-full max-w-md">
    <div class="text-center mb-8">
        <a href="/" class="inline-flex items-center gap-3 group">
            <span class="flex size-12 items-center justify-center rounded-2xl bg-white/15 text-3xl leading-none shadow-lg shadow-black/20 backdrop-blur-sm group-hover:scale-105 transition-transform select-none" role="img" aria-label="Boogle">👾</span>
            <span class="text-3xl font-bold text-white">Boogle</span>
        </a>
        @if(isset($subtitle))
            <p class="mt-3 text-indigo-300">{{ $subtitle }}</p>
        @endif
    </div>

    <div class="bg-white rounded-2xl shadow-2xl p-8">
        @yield('content')
    </div>
</div>

</body>
</html>
