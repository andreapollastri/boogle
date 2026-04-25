@props(['variant' => 'gray'])

@php
$classes = match($variant) {
    'red'    => 'bg-red-100 text-red-700',
    'yellow' => 'bg-yellow-100 text-yellow-700',
    'green'  => 'bg-green-100 text-green-700',
    'blue'   => 'bg-blue-100 text-blue-700',
    'indigo' => 'bg-indigo-100 text-indigo-700',
    default  => 'bg-gray-100 text-gray-600',
};
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $classes }}">
    {{ $slot }}
</span>
