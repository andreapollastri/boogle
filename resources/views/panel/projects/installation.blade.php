@extends('layouts.app')
@section('title', 'Installation — ' . $project->title)

@php
$envBlock = "BOOGLE_KEY={$api_token}\nBOOGLE_PROJECT_KEY={$project->key}\nBOOGLE_SERVER=".url('/api/log');
$laravel11 = <<<'PHP'
use Boogle\Facade as Boogle;
use Illuminate\Foundation\Configuration\Exceptions;

->withExceptions(function (Exceptions $exceptions): void {
    Boogle::registerExceptionHandler($exceptions);
})
PHP;
$laravel10 = <<<'PHP'
use Boogle\Facade as Boogle;
use Throwable;

public function register(): void
{
    $this->reportable(function (Throwable $e) {
        Boogle::handle($e);
    });
}
PHP;
$steps = [
    ['Install the client package', 'In the Laravel app you want to monitor.', "composer require andreapollastri/boogle-client\nphp artisan boogle:install"],
    ['Add the project credentials to .env', 'BOOGLE_KEY is specific to this project and can be rotated from Settings.', $envBlock],
];
@endphp

@section('content')
<x-project-header :project="$project" active="installation" />

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <ol class="space-y-4 xl:col-span-2">
        @foreach($steps as $i => [$title, $desc, $code])
            <li class="card p-5">
                <div class="mb-3 flex items-center gap-3">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">{{ $i + 1 }}</span>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">{{ $title }}</h3>
                        <p class="text-xs text-gray-500">{{ $desc }}</p>
                    </div>
                </div>
                <div class="relative">
                    <pre class="code-block pr-20">{{ $code }}</pre>
                    <x-copy-button :value="$code" class="absolute top-2.5 right-2.5 !border-white/10 !bg-white/10 !text-gray-200 hover:!bg-white/20" />
                </div>
            </li>
        @endforeach

        <li class="card p-5" x-data="{ tab: 'l11' }">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">3</span>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Report exceptions</h3>
                        <p class="text-xs text-gray-500">Register the reporter where your Laravel version handles exceptions.</p>
                    </div>
                </div>
                <div class="inline-flex rounded-lg bg-gray-100 p-0.5 text-xs font-medium">
                    <button type="button" @click="tab = 'l11'" :class="tab === 'l11' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'" class="rounded-md px-3 py-1">Laravel 11+</button>
                    <button type="button" @click="tab = 'l10'" :class="tab === 'l10' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'" class="rounded-md px-3 py-1">Laravel 9 / 10</button>
                </div>
            </div>
            <div x-show="tab === 'l11'">
                <p class="mb-2 text-xs text-gray-600">In <code class="rounded bg-gray-100 px-1 py-0.5 font-mono">bootstrap/app.php</code>:</p>
                <div class="relative">
                    <pre class="code-block pr-20">{{ $laravel11 }}</pre>
                    <x-copy-button :value="$laravel11" class="absolute top-2.5 right-2.5 !border-white/10 !bg-white/10 !text-gray-200 hover:!bg-white/20" />
                </div>
            </div>
            <div x-show="tab === 'l10'" x-cloak>
                <p class="mb-2 text-xs text-gray-600">In <code class="rounded bg-gray-100 px-1 py-0.5 font-mono">app/Exceptions/Handler.php</code>:</p>
                <div class="relative">
                    <pre class="code-block pr-20">{{ $laravel10 }}</pre>
                    <x-copy-button :value="$laravel10" class="absolute top-2.5 right-2.5 !border-white/10 !bg-white/10 !text-gray-200 hover:!bg-white/20" />
                </div>
            </div>
        </li>

        <li class="card p-5">
            <div class="mb-3 flex items-center gap-3">
                <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">4</span>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Send a test exception</h3>
                    <p class="text-xs text-gray-500">It should appear in this project's exception list within seconds (with a running queue worker).</p>
                </div>
            </div>
            <div class="relative">
                <pre class="code-block pr-20">php artisan boogle:test</pre>
                <x-copy-button value="php artisan boogle:test" class="absolute top-2.5 right-2.5 !border-white/10 !bg-white/10 !text-gray-200 hover:!bg-white/20" />
            </div>
        </li>
    </ol>

    <div class="space-y-6">
        <div class="card p-5">
            <h2 class="card-title mb-3">Credentials</h2>
            <dl class="space-y-3 text-xs">
                @foreach(['Project key' => $project->key, 'API token' => $api_token, 'Ingestion endpoint' => url('/api/log')] as $label => $value)
                    <div>
                        <dt class="mb-1 text-gray-500">{{ $label }}</dt>
                        <dd class="flex items-center gap-2">
                            <code class="min-w-0 flex-1 truncate rounded-md bg-gray-100 px-2 py-1 font-mono text-gray-800">{{ $value }}</code>
                            <x-copy-button :value="$value" />
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-5">
            <p class="text-sm font-medium text-indigo-900">Reporting only runs in allowed environments</p>
            <p class="mt-1 text-xs text-indigo-700">By default the client reports from <span class="font-mono">production</span> only. Adjust <span class="font-mono">environments</span> in <span class="font-mono">config/boogle.php</span> of the monitored app to test elsewhere.</p>
        </div>
    </div>
</div>
@endsection
