@props(['series' => [], 'height' => 28, 'tone' => 'indigo'])

@php
$totals = collect($series)->pluck('total');
$max = max(1, (int) $totals->max());
$bar = $tone === 'red' ? 'bg-red-400' : 'bg-indigo-400';
@endphp

<div {{ $attributes->merge(['class' => 'flex items-end gap-[3px]']) }} style="height: {{ $height }}px" aria-hidden="true">
    @foreach($series as $point)
        <div class="flex-1 rounded-sm {{ $point['total'] > 0 ? $bar : 'bg-gray-200' }}"
             style="height: {{ $point['total'] > 0 ? max(4, (int) round($point['total'] / $max * $height)) : 2 }}px"
             title="{{ $point['label'] }} — {{ $point['total'] }} {{ \Illuminate\Support\Str::plural('exception', $point['total']) }}"></div>
    @endforeach
</div>
