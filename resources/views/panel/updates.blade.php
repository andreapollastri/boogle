@extends('layouts.app')
@section('title', 'Updates')

@php
$channel = $updater->channel();
$available = $updater->available();
$waiting = $updater->waiting();
$installed = $state['current'] ?? $updater->installed();
$newest = $channel === 'beta' ? ($state['main'] ?? null) : ($state['latest'] ?? null);
$when = fn (?string $time) => $time ? \Illuminate\Support\Carbon::parse($time)->diffForHumans() : null;
@endphp

@section('content')
<x-page-header title="Updates" description="The release Boogle runs, the builds it follows and its nightly update.">
    <x-slot:actions>
        <form method="POST" action="{{ route('panel.updates.check') }}">
            @csrf
            <x-btn variant="secondary" size="sm" :disabled="! $managed">Check now</x-btn>
        </form>
        <form method="POST" action="{{ route('panel.updates.update') }}"
              onsubmit="return confirm('Force an update now? Boogle installs the newest build of its channel even when it is the one already installed, with the same database backup and rollback as every update. It never goes back to an older build.')">
            @csrf
            <input type="hidden" name="force" value="1">
            <x-btn variant="secondary" size="sm" :disabled="! $managed || $busy">Force update</x-btn>
        </form>
        @if($available)
            <form method="POST" action="{{ route('panel.updates.update') }}"
                  onsubmit="return confirm('Update Boogle to {{ $available }} now? It backs up the database, installs and migrates the new build, and puts everything back if a step fails. Boogle is in maintenance for a moment.')">
                @csrf
                <x-btn size="sm" :disabled="! $managed || $busy">Update to {{ $available }}</x-btn>
            </form>
        @endif
    </x-slot:actions>
</x-page-header>

@unless($managed)
    <div class="mb-5 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
        @if(! config('updates.enabled'))
            Updates are off on this installation (a local environment, or <code>APP_UPDATES=false</code>): update it with git.
        @else
            Boogle updates itself when it is a git checkout, or a release of a deploy (<code>releases/</code>, <code>current</code>, <code>shared/</code>, as Cipi and Deployer make them). This installation is neither: update it the way it was installed.
        @endif
    </div>
@elseif(! $updater->schedulerRunning())
    <div class="mb-5 rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
        The scheduler has not run in the last few minutes. Updates asked for here, and the nightly one, start from it: add <code>* * * * * php artisan schedule:run</code> to the crontab of the user that runs Boogle.
    </div>
@endunless

@if($busy)
    <div x-data="{ message: @js($updater->running() ? ($state['message'] ?? 'The update is running…') : 'The update starts within a minute…') }"
         x-init="setInterval(async () => {
             try {
                 const response = await fetch(@js(route('panel.updates.status')), { headers: { Accept: 'application/json' } });
                 if (! response.ok) { message = 'Boogle is switching to the new build…'; return; }
                 const status = await response.json();
                 if (! status.busy) { window.location.reload(); return; }
                 message = status.status === 'running' ? (status.message || message) : message;
             } catch (e) { message = 'Boogle is switching to the new build…'; }
         }, 3000)"
         class="mb-5 flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800" role="status">
        <svg class="size-5 shrink-0 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
        <span class="flex-1" x-text="message"></span>
        <span class="text-xs text-blue-600">This page reloads itself when it is done.</span>
    </div>
@endif

<div class="grid gap-5 lg:grid-cols-3">
    <div class="card p-5">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Installed</p>
        <p class="mt-1 text-lg font-semibold text-gray-900">{{ $updater->version() }}</p>
        <p class="mt-0.5 text-xs text-gray-500">
            Build <a href="{{ $updater->releaseUrl($installed) }}" target="_blank" rel="noopener" class="font-mono text-indigo-600 hover:underline">{{ $installed }}</a>
        </p>
    </div>

    <div class="card p-5">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $channel === 'beta' ? 'Newest on main' : 'Newest release' }}</p>
        <p class="mt-1 flex items-center gap-2 text-lg font-semibold text-gray-900">
            @if($newest)
                <a href="{{ $updater->releaseUrl($newest) }}" target="_blank" rel="noopener" class="hover:underline">{{ $newest }}</a>
            @else
                <span class="text-gray-400">—</span>
            @endif
            @if($available)
                <x-badge variant="yellow">update available</x-badge>
            @elseif(isset($state['checked_at']) && ! $waiting)
                <x-badge variant="green">up to date</x-badge>
            @endif
        </p>
        <p class="mt-0.5 text-xs text-gray-500">
            {{ isset($state['checked_at']) ? 'Checked '.$when($state['checked_at']) : 'Not checked yet' }}
        </p>
        @if($waiting)
            <p class="mt-2 text-xs text-gray-600">{{ $waiting }}</p>
        @endif
    </div>

    <div class="card p-5">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Automatic updates</p>
        <form method="POST" action="{{ route('panel.updates.automatic') }}" class="mt-2 flex items-center justify-between gap-3">
            @csrf
            @method('PATCH')
            <input type="hidden" name="auto" value="{{ $updater->automatic() ? 0 : 1 }}">
            <span class="text-sm text-gray-700">{{ $updater->automatic() ? 'Every night at '.config('updates.time') : 'Off' }}</span>
            <x-btn variant="secondary" size="sm" :disabled="! $managed">{{ $updater->automatic() ? 'Turn off' : 'Turn on' }}</x-btn>
        </form>
        <p class="mt-2 text-xs text-gray-500">The nightly run installs the newest build of the channel when there is one.</p>
    </div>
</div>

<div class="card mt-5">
    <div class="card-header flex-wrap">
        <div class="min-w-0 flex-1 basis-72">
            <h2 class="card-title">Channel</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                @if($channel === 'beta')
                    Beta tester mode: Boogle follows the main branch and installs every new commit on it, before it becomes a release.
                @else
                    Stable: Boogle installs the release tags (MAJOR.MINOR.PATCH) and nothing in between.
                @endif
            </p>
        </div>
        <form method="POST" action="{{ route('panel.updates.channel') }}" class="inline-flex shrink-0 rounded-lg border border-gray-200 bg-gray-50 p-0.5">
            @csrf
            @method('PATCH')
            @foreach(['stable' => 'Stable releases', 'beta' => 'Beta (main branch)'] as $value => $label)
                <button type="submit" name="channel" value="{{ $value }}" @disabled(! $managed || $busy)
                        class="whitespace-nowrap rounded-md px-3 py-1.5 text-xs font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50 {{ $channel === $value ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}">
                    {{ $label }}
                </button>
            @endforeach
        </form>
    </div>
    <p class="px-5 py-3 text-xs text-gray-500">Neither channel goes backwards: back on stable after beta, Boogle keeps its main build until a release contains it.</p>
</div>

@if(isset($state['status']) && $state['status'] !== 'running')
    <div class="card mt-5" x-data="{ open: {{ ($state['status'] ?? '') === 'failed' ? 'true' : 'false' }} }">
        <div class="card-header">
            <div class="flex items-center gap-2">
                <h2 class="card-title">Last update</h2>
                <x-badge :variant="match ($state['status']) { 'ok', 'up-to-date' => 'green', 'failed' => 'red', default => 'gray' }">
                    {{ match ($state['status']) { 'ok' => 'updated', 'up-to-date' => 'up to date', 'failed' => 'failed', default => $state['status'] } }}
                </x-badge>
                @if($state['triggered_automatically'] ?? false)
                    <span class="text-xs text-gray-400">nightly</span>
                @endif
            </div>
            <span class="text-xs text-gray-500">{{ $when($state['finished_at'] ?? null) }}</span>
        </div>
        <div class="px-5 py-3 text-sm text-gray-700">{{ $state['message'] ?? '' }}</div>
        @if(! empty($state['log']))
            <div class="border-t border-gray-100 px-5 py-3">
                <button type="button" @click="open = ! open" class="text-xs font-medium text-indigo-600 hover:underline" x-text="open ? 'Hide the log' : 'Show the log'"></button>
                <pre x-show="open" x-cloak class="mt-2 max-h-96 overflow-auto rounded-lg bg-gray-900 p-3 text-[11px] leading-relaxed text-gray-100">{{ implode("\n", $state['log']) }}</pre>
            </div>
        @endif
        @if(($state['status'] ?? '') === 'failed')
            <p class="border-t border-gray-100 px-5 py-2 text-xs text-gray-500">The previous build and its database are back in place. The full log is in storage/app/updates/update.log.</p>
        @endif
    </div>
@endif

<p class="mt-5 text-xs text-gray-500">
    Each update backs up the database, installs the new build (in place for a git checkout, next to the running release for a deploy), installs its dependencies, migrates and checks that Boogle boots; if a step fails, the previous build and the database come back.
</p>
@endsection
