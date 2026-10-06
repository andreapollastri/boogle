@props(['status' => 'online'])

@php
[$classes, $label] = match($status) {
    'offline'  => ['bg-red-50 text-red-700 ring-red-600/15', 'Offline'],
    'disabled' => ['bg-gray-50 text-gray-600 ring-gray-500/15', 'Uptime off'],
    default    => ['bg-emerald-50 text-emerald-700 ring-emerald-600/15', 'Online'],
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset $classes"]) }}>
    <x-health-dot :status="$status" size="size-1.5" class="!ring-0" />
    {{ $label }}
</span>
