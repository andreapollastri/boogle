@extends('layouts.app')
@section('title', 'Installation')

@section('content')
<x-page-header title="Installation guide" :description="'Set up Boogle in ' . $project->title"
    :breadcrumbs="[
        ['label'=>'Projects','href'=>route('panel.projects.index')],
        ['label'=>$project->title,'href'=>route('panel.projects.show',$project)],
        ['label'=>'Installation']
    ]" />

<div class="max-w-3xl space-y-5">

    @foreach([
        ['1', 'Install the Boogle package', null, 'composer require andreapollastri/boogle-client'],
        ['2', 'Publish configuration', null, 'php artisan boogle:install'],
    ] as [$n, $title, $desc, $code])
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex items-center justify-center w-7 h-7 rounded-full bg-indigo-600 text-white text-xs font-bold shrink-0">{{ $n }}</div>
                <h3 class="text-sm font-semibold text-gray-900">{{ $title }}</h3>
            </div>
            @if($desc)<p class="text-sm text-gray-600 mb-3">{{ $desc }}</p>@endif
            <div class="relative">
                <pre class="bg-gray-900 text-green-400 text-xs font-mono rounded-xl p-4 overflow-x-auto">{{ $code }}</pre>
            </div>
        </div>
    @endforeach

    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex items-center justify-center w-7 h-7 rounded-full bg-indigo-600 text-white text-xs font-bold shrink-0">3</div>
            <h3 class="text-sm font-semibold text-gray-900">Add project credentials to .env</h3>
        </div>
        <div class="relative">
            <pre class="bg-gray-900 text-green-400 text-xs font-mono rounded-xl p-4 overflow-x-auto">BOOGLE_KEY={{ $api_token }}
BOOGLE_PROJECT_KEY={{ $project->key }}
BOOGLE_SERVER={{ url('/api/log') }}</pre>
        </div>
        <p class="mt-3 text-xs text-gray-500">`BOOGLE_KEY` is project-specific and can be rotated from this project's settings.</p>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex items-center justify-center w-7 h-7 rounded-full bg-indigo-600 text-white text-xs font-bold shrink-0">4</div>
            <h3 class="text-sm font-semibold text-gray-900">Report exceptions</h3>
        </div>
        <p class="text-sm font-medium text-gray-800 mb-2">Laravel 11+ — <code class="text-xs font-mono bg-gray-100 px-1 py-0.5 rounded">bootstrap/app.php</code></p>
        <pre class="bg-gray-900 text-green-400 text-xs font-mono rounded-xl p-4 overflow-x-auto">use Boogle\Facade as Boogle;
use Illuminate\Foundation\Configuration\Exceptions;

->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->reportable(function (\Throwable $e) {
        Boogle::handle($e);
    });
})->create();</pre>
        <p class="text-sm text-gray-600 mt-3 mb-3">Or using the shorthand helper:</p>
        <pre class="bg-gray-900 text-green-400 text-xs font-mono rounded-xl p-4 overflow-x-auto">->withExceptions(function (Exceptions $exceptions) {
    Boogle::registerExceptionHandler($exceptions);
})</pre>
        <p class="text-sm font-medium text-gray-800 mt-5 mb-2">Laravel 9 / 10 — <code class="text-xs font-mono bg-gray-100 px-1 py-0.5 rounded">app/Exceptions/Handler.php</code></p>
        <p class="text-sm text-gray-600 mb-3">In the <code class="text-xs font-mono bg-gray-100 px-1 py-0.5 rounded">register()</code> method:</p>
        <pre class="bg-gray-900 text-green-400 text-xs font-mono rounded-xl p-4 overflow-x-auto">use Boogle\Facade as Boogle;
use Throwable;

public function register(): void
{
    $this->reportable(function (Throwable $e) {
        Boogle::handle($e);
    });
}</pre>
        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200">
            <table class="min-w-full text-xs">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Laravel version</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-600">Where to configure</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr>
                        <td class="px-4 py-2 text-gray-700 font-medium">11+</td>
                        <td class="px-4 py-2 text-gray-600 font-mono">bootstrap/app.php → withExceptions()</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 text-gray-700 font-medium">9 / 10</td>
                        <td class="px-4 py-2 text-gray-600 font-mono">app/Exceptions/Handler.php → register()</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex items-center justify-center w-7 h-7 rounded-full bg-indigo-600 text-white text-xs font-bold shrink-0">5</div>
            <h3 class="text-sm font-semibold text-gray-900">Test your setup</h3>
        </div>
        <pre class="bg-gray-900 text-green-400 text-xs font-mono rounded-xl p-4 overflow-x-auto">php artisan boogle:test</pre>
    </div>

    <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6 flex items-start gap-4">
        <svg class="w-5 h-5 text-indigo-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
            <p class="text-sm font-medium text-indigo-900 mb-1">All set!</p>
            <p class="text-sm text-indigo-700">Once installed, exceptions from your app will appear in Boogle automatically.</p>
        </div>
    </div>
</div>
@endsection
