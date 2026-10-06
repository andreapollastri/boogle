@props(['label', 'value', 'hint' => null, 'href' => null, 'tone' => 'default'])

@php
$valueClass = match($tone) {
    'danger'  => 'text-red-600',
    'warning' => 'text-amber-600',
    'success' => 'text-emerald-600',
    'muted'   => 'text-gray-400',
    default   => 'text-gray-900',
};
$tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'card block p-4 sm:p-5'.($href ? ' group transition hover:border-indigo-300 hover:shadow-sm' : '')]) }}>
    <p class="flex items-center justify-between gap-2 text-xs font-medium text-gray-500">
        {{ $label }}
        @if($href)
            <svg class="size-3.5 text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-indigo-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        @endif
    </p>
    <p class="mt-1.5 text-2xl font-semibold tracking-tight tabular-nums {{ $valueClass }}">{{ $value }}</p>
    @if($hint)
        <p class="mt-0.5 truncate text-xs text-gray-400">{{ $hint }}</p>
    @endif
</{{ $tag }}>
