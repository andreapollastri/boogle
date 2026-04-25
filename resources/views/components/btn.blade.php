@props(['variant' => 'primary', 'href' => null])

@php
$classes = match($variant) {
    'secondary' => 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 shadow-sm',
    'danger'    => 'bg-red-600 text-white hover:bg-red-700 shadow-sm',
    'ghost'     => 'text-gray-600 hover:text-gray-900 hover:bg-gray-100',
    default     => 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm',
};
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 $classes"]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed $classes"]) }}>
        {{ $slot }}
    </button>
@endif
