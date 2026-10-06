@props(['status'])

@php
[$classes, $dot] = match($status) {
    'OPEN'  => ['bg-red-50 text-red-700 ring-red-600/15', 'bg-red-500'],
    'READ'  => ['bg-amber-50 text-amber-800 ring-amber-600/20', 'bg-amber-500'],
    'FIXED' => ['bg-sky-50 text-sky-700 ring-sky-600/15', 'bg-sky-500'],
    'DONE'  => ['bg-emerald-50 text-emerald-700 ring-emerald-600/15', 'bg-emerald-500'],
    default => ['bg-gray-50 text-gray-600 ring-gray-500/15', 'bg-gray-400'],
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold tracking-wide ring-1 ring-inset $classes"]) }}>
    <span class="size-1.5 rounded-full {{ $dot }}"></span>
    {{ $status }}
</span>
