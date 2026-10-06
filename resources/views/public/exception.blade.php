<!DOCTYPE html>
<html lang="en" class="bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $exception->issue_code ? $exception->issue_code.' · ' : '' }}{{ class_basename((string) $exception->exception) ?: 'Exception' }} — Boogle</title>
    <link rel="icon" href="{{ 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y="0.9em" font-size="85" font-family="system-ui,Apple Color Emoji,Segoe UI Emoji,Noto Color Emoji,sans-serif">👾</text></svg>') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|jetbrains-mono:400,500&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">

@php
$shortName = class_basename((string) $exception->exception) ?: 'Exception';
$root = \App\Support\SourcePath::root(collect($exception->executor ?? [])->map(fn ($f) => is_array($f) ? ($f['file'] ?? null) : $f)->push($exception->file));
@endphp

<header class="border-b border-gray-200 bg-white">
    <div class="mx-auto flex max-w-5xl flex-wrap items-center gap-3 px-6 py-4">
        <span class="flex size-7 items-center justify-center rounded-lg bg-indigo-100 text-base leading-none select-none" role="img" aria-label="Boogle">👾</span>
        <span class="text-sm font-bold">Boogle</span>
        <span class="text-gray-300">/</span>
        <span class="text-sm text-gray-500">Shared exception from <strong class="text-gray-800">{{ $project->title }}</strong></span>
        <div class="ml-auto flex items-center gap-2" x-data="exceptionExport">
            <x-btn type="button" variant="secondary" size="sm" @click="markdown()"><span x-text="copied === 'md' ? 'Copied!' : 'Copy Markdown'">Copy Markdown</span></x-btn>
            <x-btn type="button" variant="secondary" size="sm" @click="prompt()"><span x-text="copied === 'prompt' ? 'Copied!' : 'Copy AI prompt'">Copy AI prompt</span></x-btn>
        </div>
    </div>
</header>

<main class="mx-auto max-w-5xl space-y-6 px-6 py-8">
    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-gray-100 px-5 py-3">
            @if($exception->issue_code)
                <span class="rounded-md border border-indigo-100 bg-indigo-50 px-2 py-0.5 font-mono text-xs font-semibold text-indigo-700">{{ $exception->issue_code }}</span>
            @endif
            <x-status-badge :status="$exception->status" />
            <span class="text-xs text-gray-500">{{ $exception->created_at->format('d M Y, H:i') }}</span>
            @if($exception->host)
                <span class="font-mono text-xs text-gray-400">{{ $exception->host }}</span>
            @endif
        </div>
        <div class="p-5">
            <h1 class="text-lg font-semibold tracking-tight break-words">{{ $shortName }}</h1>
            @if($shortName !== $exception->exception)
                <p class="mt-0.5 font-mono text-xs break-all text-gray-400">{{ $exception->exception }}</p>
            @endif
            @if($exception->error)
                <pre class="mt-4 overflow-x-auto rounded-lg border border-red-100 bg-red-50 px-4 py-3 font-mono text-[13px] leading-relaxed break-words whitespace-pre-wrap text-red-700">{{ $exception->error }}</pre>
            @endif
            @if($exception->file)
                <p class="mt-3 font-mono text-xs break-all text-gray-600">{{ \App\Support\SourcePath::relative($exception->file, $root) }}<span class="text-indigo-500">:{{ $exception->line }}</span></p>
            @endif
        </div>
    </div>

    @unless(\App\Models\ExceptionRecord::isOutageExceptionType($exception->exception))
        @include('panel.exceptions.partials.stack-trace')
    @endunless
    @include('panel.exceptions.partials.request')

    @if(! empty($exception->storage) && is_array($exception->storage))
        <div class="card p-5">
            <h2 class="card-title mb-3">Runtime</h2>
            <dl class="grid grid-cols-2 gap-3 text-xs sm:grid-cols-4">
                @foreach($exception->storage as $key => $value)
                    <div>
                        <dt class="text-gray-500">{{ ['php' => 'PHP', 'laravel' => 'Laravel', 'driver' => 'Database', 'memory' => 'Memory'][$key] ?? $key }}</dt>
                        <dd class="mt-0.5 font-mono text-gray-900">{{ is_scalar($value) ? $value : json_encode($value) }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endif

    <p class="text-center text-xs text-gray-400">Shared read-only with <a href="https://github.com/andreapollastri/boogle" class="underline hover:text-gray-600">Boogle</a>, a self-hosted exception tracker.</p>
</main>

@include('panel.exceptions.partials.export-data', ['public' => true])
</body>
</html>
