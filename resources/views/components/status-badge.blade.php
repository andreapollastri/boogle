@props(['status'])

@php
$classes = match($status) {
    'OPEN'  => 'bg-red-100 text-red-700',
    'READ'  => 'bg-yellow-100 text-yellow-700',
    'FIXED' => 'bg-blue-100 text-blue-700',
    'DONE'  => 'bg-green-100 text-green-700',
    default => 'bg-gray-100 text-gray-600',
};
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $classes }}">
    {{ $status }}
</span>
