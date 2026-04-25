<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $exception->exception ?: 'Exception' }} — Boogle</title>
    <link rel="icon" href="{{ 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y="0.9em" font-size="85" font-family="system-ui,Apple Color Emoji,Segoe UI Emoji,Noto Color Emoji,sans-serif">👾</text></svg>') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-gray-50 min-h-screen">

<header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center gap-3">
    <span class="flex size-7 items-center justify-center rounded-lg bg-indigo-100 text-base leading-none select-none" role="img" aria-label="Boogle">👾</span>
    <span class="text-sm font-bold text-gray-900">Boogle</span>
    <span class="text-gray-300 mx-2">|</span>
    <span class="text-sm text-gray-500">Shared exception from <strong>{{ $project->title }}</strong></span>
</header>

<div class="max-w-4xl mx-auto p-6 space-y-5">
    <div class="flex items-center gap-2 justify-end">
        <button type="button" onclick="copyExceptionMd(this)"
                class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            MD
        </button>
        <button type="button" onclick="copyAiPrompt(this)"
                class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .03 2.798-1.414 2.798H4.213c-1.444 0-2.414-1.798-1.414-2.798L4.8 15.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Prompt
        </button>
    </div>
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex items-center flex-wrap gap-x-3 gap-y-1 mb-4">
            @if($exception->issue_code)
                <span class="text-sm font-mono font-semibold text-indigo-700 bg-indigo-50 border border-indigo-100 rounded-lg px-2.5 py-0.5">{{ $exception->issue_code }}</span>
            @endif
            <x-status-badge :status="$exception->status" />
            <span class="text-xs text-gray-500">{{ $exception->created_at->format('d M Y H:i') }}</span>
            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-mono text-gray-700" title="config(app.env)">{{ config('app.env') }}</span>
        </div>
        <h1 class="text-lg font-semibold text-gray-900 mb-2">{{ $exception->exception }}</h1>
        @if($exception->error)
            <pre class="text-sm text-red-600 font-mono bg-red-50 px-4 py-3 rounded-lg whitespace-pre-wrap overflow-x-auto">{{ $exception->error }}</pre>
        @endif
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Details</h3>
        <dl class="space-y-2.5">
            @foreach(['file' => 'File', 'line' => 'Line', 'class' => 'Class', 'full_url' => 'URL', 'method' => 'Method', 'host' => 'Host'] as $field => $label)
                @if($exception->$field)
                    <div class="flex gap-3">
                        <dt class="text-xs text-gray-500 w-16 shrink-0">{{ $label }}</dt>
                        <dd class="text-xs {{ in_array($field, ['file','class','full_url']) ? 'font-mono text-gray-800' : 'text-gray-900' }} break-all">{{ $exception->$field }}</dd>
                    </div>
                @endif
            @endforeach
        </dl>
    </div>

    @if(!empty($exception->http))
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">HTTP</h3>
            <pre class="text-xs bg-gray-50 rounded-lg p-4 overflow-x-auto text-gray-800 max-h-80">{{ json_encode($exception->http, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @endif

    @if(!empty($exception->user))
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">User <span class="text-gray-400 font-normal">(from client)</span></h3>
            <pre class="text-xs bg-gray-50 rounded-lg p-4 overflow-x-auto text-gray-800">{{ json_encode($exception->user, JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif

    @if(!empty($exception->executor))
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Stack trace</h3>
            <pre class="text-xs bg-gray-900 text-green-400 font-mono rounded-xl p-4 overflow-x-auto max-h-80">{{ json_encode($exception->executor, JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif
</div>

@php
$exceptionData = [
    'issue_code' => $exception->issue_code,
    'exception'  => $exception->exception,
    'error'      => $exception->error,
    'status'     => $exception->status,
    'created_at' => $exception->created_at->format('d M Y H:i'),
    'host'       => $exception->host ?? null,
    'file'       => $exception->file,
    'line'       => $exception->line,
    'class'      => $exception->class,
    'method'     => $exception->method,
    'full_url'   => $exception->full_url,
    'http'       => $exception->http,
    'executor'   => $exception->executor,
    'additional' => $exception->additional ?? null,
    'user'       => $exception->user ?? null,
    'storage'    => $exception->storage ?? null,
    'env'        => config('app.env'),
];
@endphp
<script>
window.EXCEPTION_DATA = @json($exceptionData);

function buildExceptionMd() {
    const d = window.EXCEPTION_DATA;
    let md = `# Exception: ${d.exception || 'Unknown'}\n\n`;
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
</body>
</html>
