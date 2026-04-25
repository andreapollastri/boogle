@extends('layouts.app')
@section('title', $exception->exception ?: 'Exception')

@section('content')
<x-page-header :title="($exception->issue_code ? $exception->issue_code . ' — ' : '') . (Str::limit($exception->exception, 60) ?: 'Exception detail')"
    :breadcrumbs="[
        ['label'=>'Projects','href'=>route('panel.projects.index')],
        ['label'=>$project->title,'href'=>route('panel.projects.exceptions.index',$project)],
        ['label'=>'Exception'],
    ]">
    <x-slot:actions>
        {{-- Clipboard buttons --}}
        <button type="button" onclick="copyExceptionMd(this)"
                class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            MD
        </button>
        <button type="button" onclick="copyAiPrompt(this)"
                class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .03 2.798-1.414 2.798H4.213c-1.444 0-2.414-1.798-1.414-2.798L4.8 15.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Prompt
        </button>
        {{-- Publish toggle --}}
        <form method="POST" action="{{ route('panel.projects.exceptions.publish', [$project, $exception]) }}">
            @csrf @method('PATCH')
            <x-btn type="submit" variant="secondary">
                @unless($exception->publish_hash)
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.82m5.84-2.56a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.818m2.342-7.716V3a14.98 14.98 0 00-8.293 12.957"/></svg>
                @endunless
                {{ $exception->publish_hash ? 'Unpublish' : 'Publish' }}
            </x-btn>
        </form>
    </x-slot:actions>
</x-page-header>

@if($exception->publish_hash)
    <div class="mb-5 flex items-center gap-3 px-4 py-3 bg-blue-50 border border-blue-200 rounded-xl text-blue-700 text-sm">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
        Public URL:
        <a href="{{ route('exceptions.public', $exception->publish_hash) }}" target="_blank"
           class="font-mono text-xs underline hover:text-blue-900">{{ url('/e/' . $exception->publish_hash) }}</a>
    </div>
@endif

<div class="space-y-5">
    {{-- Summary --}}
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center flex-wrap gap-x-3 gap-y-1">
            @if($exception->issue_code)
                <span class="text-sm font-mono font-semibold text-indigo-700 bg-indigo-50 border border-indigo-100 rounded-lg px-2.5 py-0.5">{{ $exception->issue_code }}</span>
            @endif
            <x-status-badge :status="$exception->status" />
            <span class="text-xs text-gray-500">{{ $exception->created_at->format('d M Y H:i') }}</span>
            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-mono text-gray-700" title="config(app.env)">{{ config('app.env') }}</span>
            @if($exception->host)
                <span class="text-xs text-gray-400">{{ $exception->host }}</span>
            @endif
        </div>
        <div class="p-6">
            <p class="text-sm font-semibold text-gray-900">{{ $exception->exception }}</p>
            @if($exception->error)
                <pre class="text-sm text-red-600 font-mono bg-red-50 px-4 py-3 rounded-lg mt-3 whitespace-pre-wrap break-words overflow-x-auto">{{ $exception->error }}</pre>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 space-y-5">
            {{-- Location --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Location</h3>
                <dl class="space-y-2.5">
                    @foreach(['file' => 'File', 'line' => 'Line', 'class' => 'Class', 'method' => 'Method', 'full_url' => 'URL'] as $field => $label)
                        @if($exception->$field)
                            <div class="flex gap-3">
                                <dt class="text-xs text-gray-500 w-16 shrink-0">{{ $label }}</dt>
                                <dd class="text-xs {{ in_array($field, ['file','class','full_url']) ? 'font-mono text-gray-800 bg-gray-50 px-2 py-0.5 rounded' : 'text-gray-900' }} break-all">{{ $exception->$field }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </div>

            {{-- Stack trace --}}
            @if(!empty($exception->executor))
                <div class="bg-white rounded-2xl border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Stack trace</h3>
                    <div class="space-y-1 max-h-80 overflow-y-auto">
                        @foreach($exception->executor as $i => $frame)
                            <div class="px-3 py-1.5 rounded font-mono text-xs {{ $i === 0 ? 'bg-red-50 text-red-900' : 'text-gray-600 hover:bg-gray-50' }}">
                                {{ is_array($frame) ? ($frame['file'] ?? '') . ':' . ($frame['line'] ?? '') : $frame }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Additional --}}
            @if(!empty($exception->additional))
                <div class="bg-white rounded-2xl border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Additional data</h3>
                    <pre class="text-xs bg-gray-50 rounded-lg p-4 overflow-x-auto text-gray-700">{{ json_encode($exception->additional, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif

            @if(!empty($exception->http))
                <div class="bg-white rounded-2xl border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">HTTP request</h3>
                    <div class="space-y-4">
                        @foreach($exception->http as $key => $value)
                            @continue(in_array($key, ['headers', 'session'], true) && (($key === 'headers' && !config('boogle.ui.show_http_headers', true)) || ($key === 'session' && !config('boogle.ui.show_http_session', true))))
                            <div>
                                <p class="text-xs font-medium text-gray-500 mb-1.5">{{ $key }}</p>
                                @if(is_array($value))
                                    <pre class="text-xs bg-gray-50 rounded-lg p-3 overflow-x-auto text-gray-700">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                @else
                                    <p class="text-xs font-mono text-gray-800 bg-gray-50 rounded-lg px-3 py-2 break-all">{{ $value }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            @if(!empty($exception->user))
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">User <span class="text-gray-400 font-normal">(from client)</span></h3>
                    <pre class="text-xs bg-gray-50 rounded-lg p-3 overflow-x-auto text-gray-700">{{ json_encode($exception->user, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif

            @if(!empty($exception->storage))
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Storage</h3>
                    <pre class="text-xs bg-gray-50 rounded-lg p-3 overflow-x-auto text-gray-700">{{ json_encode($exception->storage, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif

            @if($occurrences->count())
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Recent occurrences</h3>
                    <ul class="space-y-2">
                        @foreach($occurrences as $occ)
                            <li>
                                <a href="{{ route('panel.projects.exceptions.show', [$project, $occ]) }}"
                                   class="flex items-center justify-between gap-2 text-xs hover:text-indigo-600">
                                    <span class="flex min-w-0 items-center gap-2">
                                        @if($occ->issue_code)
                                            <span class="shrink-0 font-mono font-medium text-indigo-600">{{ $occ->issue_code }}</span>
                                        @endif
                                        <x-status-badge :status="$occ->status" />
                                    </span>
                                    <span class="shrink-0 text-gray-400">{{ $occ->created_at->diffForHumans() }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    {{-- Same main column width as Location / HTTP (2 of 3 cols on large screens) --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="min-w-0 space-y-5 lg:col-span-2">
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-900 mb-1">Status &amp; notes</h2>
                <p class="text-xs text-gray-500 mb-4">Set the workflow state and add an optional note. Changes are listed in the history below.</p>
                <form method="POST" action="{{ route('panel.projects.exceptions.mark', [$project, $exception]) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="status-comment" class="block text-xs font-medium text-gray-700 mb-1.5">Comment <span class="text-gray-400 font-normal">(optional)</span></label>
                        <textarea name="comment" id="status-comment" rows="3" placeholder="e.g. reproduced on staging, fix merged in v2.3…"
                            class="w-full min-w-0 text-sm border border-gray-300 rounded-lg px-3 py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 placeholder:text-gray-400"></textarea>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:gap-4">
                        <div class="min-w-0 flex-1">
                            <label for="status-select" class="block text-xs font-medium text-gray-700 mb-1.5">New status</label>
                            <select name="status" id="status-select" required
                                class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                @foreach(\App\Models\ExceptionRecord::statuses() as $s)
                                    <option value="{{ $s }}" {{ $exception->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Apply status
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-900 mb-1">Status history</h2>
                <p class="text-xs text-gray-500 mb-4">Each transition is recorded with time, user (when any), source, and an optional note. Newest at the bottom.</p>
                @if($exception->statusEvents->isNotEmpty())
                    <ol class="ml-1 space-y-4 border-l-2 border-gray-200 pl-4">
                        @foreach($exception->statusEvents as $event)
                            <li class="relative min-w-0">
                                <span class="absolute -left-[calc(0.5rem+5px)] top-1.5 h-2.5 w-2.5 rounded-full bg-indigo-500 ring-4 ring-white"></span>
                                <div class="flex min-w-0 flex-wrap items-baseline gap-x-2 gap-y-0.5">
                                    <time class="text-xs text-gray-500 tabular-nums" datetime="{{ $event->created_at->toIso8601String() }}">
                                        {{ $event->created_at->format('d M Y H:i') }}
                                    </time>
                                    <span class="text-xs text-gray-400">·</span>
                                    <span class="min-w-0 text-xs font-medium text-gray-800">
                                        @if($event->user)
                                            {{ $event->user->name }} ({{ $event->user->email }})
                                        @else
                                            System
                                        @endif
                                    </span>
                                    @if($event->source === \App\Models\ExceptionStatusEvent::SOURCE_API)
                                        <span class="text-[0.65rem] font-medium uppercase tracking-wide text-indigo-600 bg-indigo-50 border border-indigo-100 rounded px-1.5 py-0.5">API</span>
                                    @elseif($event->source === \App\Models\ExceptionStatusEvent::SOURCE_BULK)
                                        <span class="text-[0.65rem] font-medium uppercase tracking-wide text-amber-700 bg-amber-50 border border-amber-100 rounded px-1.5 py-0.5">Bulk</span>
                                    @elseif($event->source === \App\Models\ExceptionStatusEvent::SOURCE_SYSTEM)
                                        <span class="text-[0.65rem] font-medium uppercase tracking-wide text-slate-600 bg-slate-100 border border-slate-200 rounded px-1.5 py-0.5">System</span>
                                    @elseif($event->source === \App\Models\ExceptionStatusEvent::SOURCE_VIEW)
                                        <span class="text-[0.65rem] font-medium uppercase tracking-wide text-teal-700 bg-teal-50 border border-teal-100 rounded px-1.5 py-0.5">View</span>
                                    @elseif($event->source === \App\Models\ExceptionStatusEvent::SOURCE_PANEL)
                                        <span class="text-[0.65rem] font-medium uppercase tracking-wide text-indigo-600 bg-indigo-50 border border-indigo-100 rounded px-1.5 py-0.5">Panel</span>
                                    @endif
                                </div>
                                <div class="mt-1 flex min-w-0 flex-wrap items-center gap-2 text-sm">
                                    <x-status-badge :status="$event->from_status" />
                                    <span class="shrink-0 text-gray-400" aria-hidden="true">→</span>
                                    <x-status-badge :status="$event->to_status" />
                                </div>
                                @if(filled($event->comment))
                                    <p class="mt-2 whitespace-pre-wrap break-words text-sm text-gray-700 rounded-lg bg-gray-50 border border-gray-100 px-3 py-2">{{ $event->comment }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="text-sm text-gray-500">No status changes recorded yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@php
$exceptionData = [
    'issue_code' => $exception->issue_code,
    'exception'  => $exception->exception,
    'error'      => $exception->error,
    'status'     => $exception->status,
    'created_at' => $exception->created_at->format('d M Y H:i'),
    'host'       => $exception->host,
    'file'       => $exception->file,
    'line'       => $exception->line,
    'class'      => $exception->class,
    'method'     => $exception->method,
    'full_url'   => $exception->full_url,
    'http'       => $exception->http,
    'executor'   => $exception->executor,
    'additional' => $exception->additional,
    'user'       => $exception->user,
    'storage'    => $exception->storage,
    'env'        => config('app.env'),
];
@endphp
<script>
window.EXCEPTION_DATA = @json($exceptionData);

function buildExceptionMd() {
    const d = window.EXCEPTION_DATA;
    let md = `# Exception: ${d.exception || 'Unknown'}\n\n`;
    if (d.issue_code) md += `**Issue:** ${d.issue_code}  \n`;
    if (d.error) md += `**Error:**\n\`\`\`\n${d.error}\n\`\`\`\n\n`;
    md += `**Date:** ${d.created_at}  \n**Status:** ${d.status}  \n`;
    if (d.env) md += `**Environment:** ${d.env}  \n`;
    if (d.host) md += `**Host:** ${d.host}  \n`;
    md += '\n';
    const locLabels = {file:'File', line:'Line', class:'Class', method:'Method', full_url:'URL'};
    const locItems = Object.entries(locLabels).filter(([k]) => d[k]);
    if (locItems.length) {
        md += `## Location\n\n`;
        locItems.forEach(([k, label]) => md += `- **${label}:** \`${d[k]}\`\n`);
        md += '\n';
    }
    if (d.executor && d.executor.length) {
        md += `## Stack Trace\n\n\`\`\`\n`;
        d.executor.forEach(f => { md += typeof f === 'object' ? `${f.file || ''}:${f.line || ''}\n` : `${f}\n`; });
        md += `\`\`\`\n\n`;
    }
    if (d.http && Object.keys(d.http).length) md += `## HTTP\n\n\`\`\`json\n${JSON.stringify(d.http, null, 2)}\n\`\`\`\n\n`;
    if (d.additional) md += `## Additional Data\n\n\`\`\`json\n${JSON.stringify(d.additional, null, 2)}\n\`\`\`\n\n`;
    if (d.user)       md += `## User (client)\n\n\`\`\`json\n${JSON.stringify(d.user, null, 2)}\n\`\`\`\n\n`;
    if (d.storage)    md += `## Storage\n\n\`\`\`json\n${JSON.stringify(d.storage, null, 2)}\n\`\`\`\n\n`;
    return md;
}

function buildAiPrompt() {
    const d = window.EXCEPTION_DATA;
    let prompt = `You are an expert software engineer. I need you to investigate and help me resolve the following exception that occurred in my application.\n\n`;
    prompt += `Please analyze the error, identify the root cause, and provide a clear explanation and concrete fix.\n\n`;
    prompt += `---\n\n`;
    prompt += `## Exception\n\n`;
    prompt += `**Type:** \`${d.exception || 'Unknown'}\`\n`;
    if (d.error) prompt += `**Message:**\n\`\`\`\n${d.error}\n\`\`\`\n`;
    prompt += `**Status:** ${d.status}  \n**Date:** ${d.created_at}  \n`;
    if (d.host) prompt += `**Host:** ${d.host}  \n`;
    if (d.env)  prompt += `**Environment:** ${d.env}  \n`;
    prompt += '\n';
    const locLabels = {file:'File', line:'Line', class:'Class', method:'Method', full_url:'URL'};
    const locItems = Object.entries(locLabels).filter(([k]) => d[k]);
    if (locItems.length) {
        prompt += `## Location\n\n`;
        locItems.forEach(([k, label]) => prompt += `- **${label}:** \`${d[k]}\`\n`);
        prompt += '\n';
    }
    if (d.executor && d.executor.length) {
        prompt += `## Stack Trace\n\n\`\`\`\n`;
        d.executor.forEach(f => { prompt += typeof f === 'object' ? `${f.file || ''}:${f.line || ''}\n` : `${f}\n`; });
        prompt += `\`\`\`\n\n`;
    }
    if (d.http && Object.keys(d.http).length) prompt += `## HTTP\n\n\`\`\`json\n${JSON.stringify(d.http, null, 2)}\n\`\`\`\n\n`;
    if (d.additional) prompt += `## Additional Context\n\n\`\`\`json\n${JSON.stringify(d.additional, null, 2)}\n\`\`\`\n\n`;
    if (d.user)       prompt += `## User (client)\n\n\`\`\`json\n${JSON.stringify(d.user, null, 2)}\n\`\`\`\n\n`;
    if (d.storage)    prompt += `## Storage / Environment\n\n\`\`\`json\n${JSON.stringify(d.storage, null, 2)}\n\`\`\`\n\n`;
    prompt += `---\n\n`;
    prompt += `## What I need from you\n\n`;
    prompt += `1. **Root cause analysis** — explain why this exception is occurring\n`;
    prompt += `2. **Step-by-step fix** — provide the code changes or configuration needed to resolve it\n`;
    prompt += `3. **Prevention** — suggest how to prevent this type of error in the future\n`;
    return prompt;
}

function copyExceptionMd(btn) {
    navigator.clipboard.writeText(buildExceptionMd()).then(() => flashBtn(btn, 'Copied!'));
}

function copyAiPrompt(btn) {
    navigator.clipboard.writeText(buildAiPrompt()).then(() => flashBtn(btn, 'Copied!'));
}

function flashBtn(btn, label) {
    const orig = btn.innerHTML;
    btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> ' + label;
    btn.disabled = true;
    setTimeout(() => { btn.innerHTML = orig; btn.disabled = false; }, 2000);
}
</script>
@endsection
