@props(['status' => 'online', 'size' => 'size-2'])

@php
$classes = match($status) {
    'offline'  => 'bg-red-500 ring-red-500/25 animate-pulse',
    'disabled' => 'bg-gray-300 ring-gray-300/30',
    'warning'  => 'bg-amber-400 ring-amber-400/25',
    default    => 'bg-emerald-500 ring-emerald-500/25',
};
$label = match($status) {
    'offline'  => 'Offline',
    'disabled' => 'Uptime checks off',
    'warning'  => 'Open issues',
    default    => 'Online',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-block shrink-0 rounded-full ring-4 $size $classes"]) }} title="{{ $label }}" aria-label="{{ $label }}"></span>
