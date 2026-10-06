@extends('layouts.app')
@section('title', ($exception->issue_code ? $exception->issue_code.' ' : '') . class_basename((string) $exception->exception))

@php
$isOutage = \App\Models\ExceptionRecord::isOutageExceptionType($exception->exception);
$listUrl = route('panel.projects.exceptions.index', array_merge(['project' => $project], $filters->toQuery()));
$statusLabel = ['ALL' => 'all exceptions', 'OPEN' => 'Open', 'READ' => 'Read', 'FIXED' => 'Fixed', 'DONE' => 'Done'][$filters->status];
$root = \App\Support\SourcePath::root(collect($exception->executor ?? [])->map(fn ($f) => is_array($f) ? ($f['file'] ?? null) : $f)->push($exception->file));
$shortName = class_basename((string) $exception->exception) ?: 'Exception';
$namespace = $shortName !== $exception->exception ? Str::beforeLast((string) $exception->exception, '\\') : null;
$storage = is_array($exception->storage) ? array_filter($exception->storage, fn ($v) => $v !== null && $v !== '') : [];
$appUser = is_array($exception->user) ? array_filter($exception->user, fn ($v) => $v !== null && $v !== '') : [];
$statusMeta = [
    'OPEN' => ['Open', 'Needs a look', 'bg-red-500'],
    'READ' => ['Read', 'Acknowledged', 'bg-amber-500'],
    'FIXED' => ['Fixed', 'Fix shipped, verifying', 'bg-sky-500'],
    'DONE' => ['Done', 'Verified & closed', 'bg-emerald-500'],
];
@endphp

@section('content')
<x-project-header :project="$project" active="exceptions" :crumb="$exception->issue_code" />

{{-- Toolbar: list context, newer / older, exports --}}
<div class="mb-5 flex flex-wrap items-center gap-2">
    <a href="{{ $listUrl }}" data-shortcut="back"
       class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-gray-600 hover:bg-white hover:text-gray-900 hover:shadow-sm">
        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to list
    </a>

    <div class="flex items-center rounded-lg border border-gray-200 bg-white shadow-sm">
        @if($neighbours['newer'])
            <a href="{{ route('panel.projects.exceptions.show', [$project, $neighbours['newer']]) }}" data-shortcut="newer" title="Newer ({{ $neighbours['newer']->issue_code }}) — K"
               class="inline-flex items-center gap-1 rounded-l-lg px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg> Newer
            </a>
        @else
            <span class="inline-flex cursor-not-allowed items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-300">
                <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg> Newer
            </span>
        @endif
        <span class="border-x border-gray-200 px-2.5 py-1.5 text-xs text-gray-500 tabular-nums">
            @if($neighbours['position'])
                {{ number_format($neighbours['position']) }} / {{ number_format($neighbours['total']) }}
            @else
                {{ number_format($neighbours['total']) }} left
            @endif
        </span>
        @if($neighbours['older'])
            <a href="{{ route('panel.projects.exceptions.show', [$project, $neighbours['older']]) }}" data-shortcut="older" title="Older ({{ $neighbours['older']->issue_code }}) — J"
               class="inline-flex items-center gap-1 rounded-r-lg px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                Older <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </a>
        @else
            <span class="inline-flex cursor-not-allowed items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-300">
                Older <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </span>
        @endif
    </div>
    <span class="hidden text-xs text-gray-400 md:inline">in {{ $statusLabel }}{{ $filters->isNarrowed() ? ' (filtered)' : '' }}</span>

    <div class="ml-auto flex flex-wrap items-center gap-2" x-data="exceptionExport">
        <x-btn type="button" variant="secondary" size="sm" @click="markdown()">
            <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            <span x-text="copied === 'md' ? 'Copied!' : 'Copy Markdown'">Copy Markdown</span>
        </x-btn>
        <x-btn type="button" variant="secondary" size="sm" @click="prompt()">
            <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
            <span x-text="copied === 'prompt' ? 'Copied!' : 'Copy AI prompt'">Copy AI prompt</span>
        </x-btn>
        <form method="POST" action="{{ route('panel.projects.exceptions.publish', [$project, $exception]) }}">
            @csrf @method('PATCH')
            <x-btn type="submit" variant="secondary" size="sm" title="{{ $exception->publish_hash ? 'Disable the public link' : 'Create a public, read-only link' }}">
                <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                {{ $exception->publish_hash ? 'Unshare' : 'Share link' }}
            </x-btn>
        </form>
    </div>
</div>

@if($exception->publish_hash)
    <div class="mb-5 flex flex-wrap items-center gap-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-2.5 text-sm text-sky-800">
        <svg class="size-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Anyone with the link can view this exception:</span>
        <a href="{{ route('exceptions.public', $exception->publish_hash) }}" target="_blank" class="min-w-0 truncate font-mono text-xs underline hover:text-sky-950">{{ route('exceptions.public', $exception->publish_hash) }}</a>
        <x-copy-button :value="route('exceptions.public', $exception->publish_hash)" class="ml-auto" />
    </div>
@endif

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="min-w-0 space-y-6 xl:col-span-2">
        {{-- Summary --}}
        <div class="card overflow-hidden">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-gray-100 px-5 py-3">
                @if($exception->issue_code)
                    <span class="rounded-md border border-indigo-100 bg-indigo-50 px-2 py-0.5 font-mono text-xs font-semibold text-indigo-700">{{ $exception->issue_code }}</span>
                @endif
                <x-status-badge :status="$exception->status" />
                <span class="text-xs text-gray-500" title="{{ $exception->created_at->toDayDateTimeString() }}">{{ $exception->created_at->format('d M Y, H:i') }} · {{ $exception->created_at->diffForHumans() }}</span>
                @if($exception->host)
                    <span class="font-mono text-xs text-gray-400">{{ $exception->host }}</span>
                @endif
            </div>
            <div class="p-5">
                <h2 class="text-lg font-semibold tracking-tight break-words text-gray-900">{{ $shortName }}</h2>
                @if($namespace)
                    <p class="mt-0.5 font-mono text-xs break-all text-gray-400">{{ $exception->exception }}</p>
                @endif
                @if($exception->error)
                    <pre class="mt-4 overflow-x-auto rounded-lg border border-red-100 bg-red-50 px-4 py-3 font-mono text-[13px] leading-relaxed break-words whitespace-pre-wrap text-red-700">{{ $exception->error }}</pre>
                @endif
                @if($exception->file)
                    <p class="mt-3 flex items-center gap-1.5 font-mono text-xs break-all text-gray-600">
                        <svg class="size-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        <span>{{ \App\Support\SourcePath::relative($exception->file, $root) }}<span class="text-indigo-500">:{{ $exception->line }}</span></span>
                    </p>
                @endif

                <dl class="mt-5 grid grid-cols-3 gap-3 border-t border-gray-100 pt-4 text-xs">
                    <div>
                        <dt class="text-gray-500">Seen</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-gray-900 tabular-nums">
                            {{ number_format($similar['total']) }} {{ Str::plural('time', $similar['total']) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">First seen</dt>
                        <dd class="mt-0.5 font-medium text-gray-900" title="{{ $similar['first_seen']->toDayDateTimeString() }}">{{ $similar['first_seen']->diffForHumans() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Last seen</dt>
                        <dd class="mt-0.5 font-medium text-gray-900" title="{{ $similar['last_seen']->toDayDateTimeString() }}">{{ $similar['last_seen']->diffForHumans() }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        @if($isOutage)
            @php $additional = $exception->additional ?? []; @endphp
            <div class="card p-5">
                <h2 class="card-title mb-4">Outage</h2>
                <dl class="grid grid-cols-1 gap-4 text-xs sm:grid-cols-3">
                    <div>
                        <dt class="text-gray-500">Went offline</dt>
                        <dd class="mt-0.5 font-medium text-gray-900">{{ $exception->created_at->format('d M Y, H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Back online</dt>
                        <dd class="mt-0.5 font-medium {{ isset($additional['recovered_at']) ? 'text-gray-900' : 'text-red-600' }}">
                            {{ isset($additional['recovered_at']) ? \Illuminate\Support\Carbon::parse($additional['recovered_at'])->format('d M Y, H:i') : 'Still offline' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Duration</dt>
                        <dd class="mt-0.5 font-medium text-gray-900">{{ $additional['offline_duration_human'] ?? $exception->created_at->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE).' so far' }}</dd>
                    </div>
                </dl>
                @if($exception->full_url)
                    <p class="mt-4 font-mono text-xs break-all text-gray-600">GET {{ $exception->full_url }}</p>
                @endif
            </div>
        @else
            @include('panel.exceptions.partials.stack-trace')
            @include('panel.exceptions.partials.request')

            @if(! empty($exception->additional))
                <div class="card p-5">
                    <h2 class="card-title mb-3">Additional data</h2>
                    <pre class="overflow-x-auto rounded-lg bg-gray-50 p-4 font-mono text-[11px] text-gray-700">{{ json_encode($exception->additional, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif
        @endif

        {{-- History --}}
        <div class="card p-5">
            <h2 class="card-title">History</h2>
            <p class="mb-5 text-xs text-gray-500">Every status change, with who made it and why.</p>
            <ol class="relative ml-1.5 space-y-5 border-l-2 border-gray-100 pl-5">
                <li class="relative">
                    <span class="absolute top-1 -left-[27px] size-3 rounded-full bg-gray-300 ring-4 ring-white"></span>
                    <p class="text-xs text-gray-500"><time datetime="{{ $exception->created_at->toIso8601String() }}">{{ $exception->created_at->format('d M Y, H:i') }}</time></p>
                    <p class="mt-0.5 text-sm text-gray-800">{{ $isOutage ? 'Uptime check failed — outage opened' : 'Reported by the client' }}</p>
                </li>
                @foreach($exception->statusEvents as $event)
                    <li class="relative min-w-0">
                        <span class="absolute top-1 -left-[27px] size-3 rounded-full ring-4 ring-white {{ $statusMeta[$event->to_status][2] ?? 'bg-indigo-500' }}"></span>
                        <div class="flex min-w-0 flex-wrap items-baseline gap-x-2 gap-y-0.5 text-xs">
                            <time class="text-gray-500 tabular-nums" datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->format('d M Y, H:i') }}</time>
                            <span class="font-medium text-gray-800">{{ $event->user?->name ?? 'Boogle' }}</span>
                            @php
                                [$sourceLabel, $sourceClass] = match ($event->source) {
                                    \App\Models\ExceptionStatusEvent::SOURCE_API => ['API', 'bg-indigo-50 text-indigo-600'],
                                    \App\Models\ExceptionStatusEvent::SOURCE_BULK => ['Bulk', 'bg-amber-50 text-amber-700'],
                                    \App\Models\ExceptionStatusEvent::SOURCE_SYSTEM => ['Automatic', 'bg-slate-100 text-slate-600'],
                                    \App\Models\ExceptionStatusEvent::SOURCE_VIEW => ['Viewed', 'bg-teal-50 text-teal-700'],
                                    default => ['Panel', 'bg-gray-100 text-gray-600'],
                                };
                            @endphp
                            <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold tracking-wide uppercase {{ $sourceClass }}">{{ $sourceLabel }}</span>
                        </div>
                        <div class="mt-1.5 flex items-center gap-2">
                            <x-status-badge :status="$event->from_status" />
                            <svg class="size-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            <x-status-badge :status="$event->to_status" />
                        </div>
                        @if(filled($event->comment))
                            <p class="mt-2 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm break-words whitespace-pre-wrap text-gray-700">{{ $event->comment }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    </div>

    {{-- Sidebar --}}
    <aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
        {{-- Triage --}}
        <form method="POST" action="{{ route('panel.projects.exceptions.mark', [$project, $exception]) }}" class="card p-5"
              x-data="{ next: (() => { try { return localStorage.getItem('boogle.triage.next') !== '0' } catch (e) { return true } })(), note: false }"
              x-init="$watch('next', v => { try { localStorage.setItem('boogle.triage.next', v ? '1' : '0') } catch (e) {} })">
            @csrf
            @method('PATCH')
            <input type="hidden" name="next" :value="next ? 1 : 0" value="1">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="card-title">Triage</h2>
                <span class="text-xs text-gray-500">Now: <span class="font-semibold text-gray-800">{{ $statusMeta[$exception->status][0] ?? $exception->status }}</span></span>
            </div>
            <div class="grid grid-cols-2 gap-2">
                @foreach($statusMeta as $value => [$label, $hint, $dot])
                    @php $current = $exception->status === $value; @endphp
                    <button type="submit" name="status" value="{{ $value }}" @disabled($current)
                            class="flex flex-col items-start rounded-xl border px-3 py-2 text-left transition
                                   {{ $current ? 'cursor-default border-indigo-300 bg-indigo-50 ring-1 ring-indigo-300' : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50' }}">
                        <span class="flex items-center gap-1.5 text-sm font-medium text-gray-900"><span class="size-2 rounded-full {{ $dot }}"></span>{{ $label }}</span>
                        <span class="mt-0.5 text-[11px] text-gray-500">{{ $current ? 'Current status' : $hint }}</span>
                    </button>
                @endforeach
            </div>

            <button type="button" x-show="! note" @click="note = true; $nextTick(() => $refs.comment.focus())" class="mt-3 text-xs font-medium text-indigo-600 hover:text-indigo-800">+ Add a note</button>
            <div x-show="note" x-cloak class="mt-3">
                <label for="status-comment" class="mb-1 block text-xs font-medium text-gray-700">Note <span class="font-normal text-gray-400">(saved with the status change)</span></label>
                <textarea x-ref="comment" name="comment" id="status-comment" rows="3" class="field" placeholder="e.g. reproduced on staging, fix merged in v2.3…"></textarea>
            </div>

            <label class="mt-4 flex items-center gap-2 border-t border-gray-100 pt-3 text-xs text-gray-600">
                <input type="checkbox" x-model="next">
                Open the next exception after updating
            </label>
        </form>

        @if($appUser)
            <div class="card p-5">
                <h2 class="card-title mb-3">Affected user</h2>
                <div class="flex items-center gap-3">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600">{{ mb_strtoupper(mb_substr((string) ($appUser['name'] ?? $appUser['email'] ?? '?'), 0, 1)) }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-900">{{ $appUser['name'] ?? 'Unknown' }}</p>
                        @isset($appUser['email'])<p class="truncate text-xs text-gray-500">{{ $appUser['email'] }}</p>@endisset
                    </div>
                </div>
                @php $extra = array_diff_key($appUser, array_flip(['name', 'email'])); @endphp
                @if($extra)
                    <dl class="mt-3 space-y-1.5 border-t border-gray-100 pt-3 text-xs">
                        @foreach($extra as $key => $value)
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">{{ $key }}</dt>
                                <dd class="truncate font-mono text-gray-900">{{ is_scalar($value) ? $value : json_encode($value) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>
        @endif

        @if($storage)
            <div class="card p-5">
                <h2 class="card-title mb-3">Runtime</h2>
                <dl class="space-y-1.5 text-xs">
                    @foreach($storage as $key => $value)
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">{{ ['php' => 'PHP', 'laravel' => 'Laravel', 'driver' => 'Database', 'memory' => 'Memory'][$key] ?? $key }}</dt>
                            <dd class="truncate font-mono text-gray-900">{{ is_scalar($value) ? $value : json_encode($value) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif

        <div class="card p-5">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="card-title">Other occurrences</h2>
                @if($similar['total'] > 1 && $exception->fingerprint)
                    <a href="{{ route('panel.projects.exceptions.index', [$project, 'fingerprint' => $exception->fingerprint]) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">All {{ $similar['total'] }} →</a>
                @endif
            </div>
            @if($occurrences->isEmpty())
                <p class="text-xs text-gray-500">This is the only time this error was reported.</p>
            @else
                <ul class="-mx-2 space-y-0.5">
                    @foreach($occurrences as $occ)
                        <li>
                            <a href="{{ route('panel.projects.exceptions.show', [$project, $occ]) }}" class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-xs hover:bg-gray-50">
                                <span class="flex min-w-0 items-center gap-2">
                                    <span class="w-16 shrink-0 font-mono font-medium text-indigo-600">{{ $occ->issue_code ?? '—' }}</span>
                                    <x-status-badge :status="$occ->status" />
                                </span>
                                <span class="shrink-0 text-gray-400" title="{{ $occ->created_at->toDayDateTimeString() }}">{{ $occ->created_at->diffForHumans(short: true) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <p class="px-1 text-[11px] text-gray-400">
            Shortcuts: <span class="kbd">J</span> older · <span class="kbd">K</span> newer · <span class="kbd">U</span> back to list
        </p>
    </aside>
</div>

@include('panel.exceptions.partials.export-data')
@endsection
