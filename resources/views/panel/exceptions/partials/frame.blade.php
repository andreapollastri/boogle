@php
$call = ! empty($frame['function'])
    ? ($frame['class'] ?? '').($frame['type'] ?? '').$frame['function'].'()'
    : null;
$file = $relative($frame['file'] ?? null);
@endphp
<li class="flex gap-3 px-5 py-2.5 {{ $vendor ? 'text-gray-400' : 'border-l-2 border-indigo-400 bg-indigo-50/30 text-gray-800' }}">
    <span class="w-5 shrink-0 pt-px text-right text-[10px] {{ $vendor ? 'text-gray-300' : 'text-indigo-400' }}">{{ $index + 1 }}</span>
    <div class="min-w-0">
        @if($call)
            <p class="break-all {{ $vendor ? '' : 'font-medium text-gray-900' }}">{{ $call }}</p>
        @endif
        <p class="break-all {{ $call ? 'mt-0.5 text-[11px]' : '' }} {{ $vendor ? 'text-gray-400' : 'text-gray-500' }}">
            {{ $file ?: '[internal function]' }}@if(! empty($frame['line']))<span class="{{ $vendor ? '' : 'text-indigo-500' }}">:{{ $frame['line'] }}</span>@endif
        </p>
    </div>
</li>
