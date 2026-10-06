@props(['value', 'label' => 'Copy', 'done' => 'Copied'])

<button type="button" x-data="copy(@js($value), @js($label), @js($done))" @click="run()"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs font-medium text-gray-600 shadow-sm transition hover:bg-gray-50 hover:text-gray-900']) }}>
    <svg x-show="!copied" class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
    <svg x-show="copied" x-cloak class="size-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
    <span x-text="label">{{ $label }}</span>
</button>
