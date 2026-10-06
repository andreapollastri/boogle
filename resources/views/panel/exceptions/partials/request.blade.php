{{-- HTTP context as sent by boogle-client (`http` block), with fallbacks for older payloads. --}}
@php
$http = is_array($exception->http) ? $exception->http : [];
$method = $http['method'] ?? $exception->method;
$url = $http['url'] ?? $exception->full_url;
$client = is_array($http['client'] ?? null) ? $http['client'] : [];
$hidden = array_filter([
    'headers' => ! config('boogle.ui.show_http_headers', true),
    'session' => ! config('boogle.ui.show_http_session', true),
]);
$facts = array_filter([
    'IP address' => $http['ip'] ?? null,
    'Browser' => $client['browser'] ?? null,
    'Operating system' => $client['os'] ?? null,
    'Content type' => $http['content_type'] ?? null,
    'Referer' => $http['referer'] ?? null,
    'User agent' => $http['user_agent'] ?? null,
]);
$flags = array_filter([
    'HTTPS' => $http['is_secure'] ?? null,
    'JSON' => $http['is_json'] ?? null,
    'Expects JSON' => $http['wants_json'] ?? null,
    'AJAX' => $http['is_ajax'] ?? null,
]);
$sections = collect(['query' => 'Query string', 'payload' => 'Payload', 'cookies' => 'Cookies', 'headers' => 'Headers', 'session' => 'Session'])
    ->filter(fn ($label, $key) => ! empty($http[$key]) && ! isset($hidden[$key]));
$known = ['url', 'method', 'ip', 'client', 'user_agent', 'content_type', 'referer', 'is_secure', 'is_json', 'wants_json', 'is_ajax', 'query', 'payload', 'cookies', 'headers', 'session'];
$other = array_filter(array_diff_key($http, array_flip($known)), fn ($v) => $v !== null && $v !== [] && $v !== '');
$methodClass = match (strtoupper((string) $method)) {
    'GET' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
    'POST' => 'bg-sky-50 text-sky-700 ring-sky-600/15',
    'PUT', 'PATCH' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
    'DELETE' => 'bg-red-50 text-red-700 ring-red-600/15',
    default => 'bg-gray-50 text-gray-600 ring-gray-500/15',
};
@endphp

@if($url || $facts || $sections->isNotEmpty())
<div class="card overflow-hidden">
    <div class="card-header">
        <h2 class="card-title">Request</h2>
        @if($flags)
            <div class="flex flex-wrap gap-1">
                @foreach($flags as $label => $on)
                    <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</span>
                @endforeach
            </div>
        @endif
    </div>
    <div class="space-y-4 p-5">
        @if($url)
            <div class="flex items-start gap-2">
                @if($method)
                    <span class="mt-px shrink-0 rounded px-1.5 py-0.5 font-mono text-[11px] font-semibold ring-1 ring-inset {{ $methodClass }}">{{ strtoupper($method) }}</span>
                @endif
                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="min-w-0 flex-1 break-all font-mono text-xs leading-5 text-gray-800 hover:text-indigo-600">{{ $url }}</a>
                <x-copy-button :value="$url" label="" done="" class="!px-1.5" title="Copy URL" />
            </div>
        @endif

        @if($facts)
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 text-xs sm:grid-cols-2">
                @foreach($facts as $label => $value)
                    <div class="{{ in_array($label, ['User agent', 'Referer'], true) ? 'sm:col-span-2' : '' }}">
                        <dt class="text-gray-500">{{ $label }}</dt>
                        <dd class="mt-0.5 break-all {{ in_array($label, ['IP address', 'User agent', 'Referer', 'Content type'], true) ? 'font-mono text-[11px]' : 'font-medium' }} text-gray-900">{{ is_scalar($value) ? $value : json_encode($value) }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif

        @foreach($sections as $key => $label)
            @php $value = $http[$key]; @endphp
            <details class="group rounded-xl border border-gray-200" @if(in_array($key, ['query', 'payload'], true)) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-3.5 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                    <span class="inline-flex items-center gap-2">
                        <svg class="size-3.5 text-gray-400 transition group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        {{ $label }}
                    </span>
                    <span class="text-[11px] text-gray-400">{{ is_array($value) ? count($value).' '.Str::plural('key', count($value)) : '' }}</span>
                </summary>
                <div class="border-t border-gray-100">
                    @if(is_array($value) && ! array_is_list($value) && collect($value)->every(fn ($v) => is_scalar($v) || $v === null))
                        <table class="w-full text-xs">
                            <tbody class="divide-y divide-gray-100">
                                @foreach($value as $k => $v)
                                    <tr>
                                        <td class="w-1/3 px-3.5 py-2 align-top font-mono text-[11px] text-gray-500">{{ $k }}</td>
                                        <td class="break-all px-3.5 py-2 font-mono text-[11px] text-gray-900">{{ is_bool($v) ? ($v ? 'true' : 'false') : ($v ?? 'null') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <pre class="overflow-x-auto p-3.5 font-mono text-[11px] leading-relaxed text-gray-700">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                    @endif
                </div>
            </details>
        @endforeach

        @if($other)
            <details class="group rounded-xl border border-gray-200">
                <summary class="cursor-pointer list-none px-3.5 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Other context</summary>
                <pre class="overflow-x-auto border-t border-gray-100 p-3.5 font-mono text-[11px] text-gray-700">{{ json_encode($other, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
            </details>
        @endif

        @if($hidden)
            <p class="text-[11px] text-gray-400">{{ implode(' and ', array_map('ucfirst', array_keys($hidden))) }} hidden by the panel privacy settings.</p>
        @endif
    </div>
</div>
@endif
