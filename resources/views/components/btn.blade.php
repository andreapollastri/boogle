@props(['variant' => 'primary', 'href' => null, 'size' => 'md'])

@php
$classes = match($variant) {
    'secondary' => 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 shadow-sm',
    'danger'    => 'bg-red-600 text-white hover:bg-red-700 shadow-sm',
    'ghost'     => 'text-gray-600 hover:text-gray-900 hover:bg-gray-100',
    default     => 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm',
};
$sizing = $size === 'sm' ? 'gap-1.5 rounded-md px-2.5 py-1.5 text-xs' : 'gap-2 rounded-lg px-4 py-2 text-sm';
$base = "inline-flex items-center justify-center font-medium transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-indigo-500 $sizing $classes";
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => "$base disabled:opacity-50 disabled:cursor-not-allowed"]) }}>
        {{ $slot }}
    </button>
@endif
