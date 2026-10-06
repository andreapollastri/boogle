@props(['name', 'namespace' => false])

@php
$name = (string) $name;
$short = class_basename($name) ?: $name;
$ns = $short !== $name ? trim(\Illuminate\Support\Str::beforeLast($name, '\\'), '\\') : null;
@endphp

<span {{ $attributes->merge(['class' => 'min-w-0']) }} title="{{ $name }}">
    <span class="font-semibold text-gray-900">{{ $short ?: 'Unknown exception' }}</span>
    @if($namespace && $ns)
        <span class="ml-1 font-mono text-[11px] font-normal text-gray-400">{{ $ns }}</span>
    @endif
</span>
