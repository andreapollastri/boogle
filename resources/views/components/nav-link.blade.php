@props(['href', 'active' => false])

<a href="{{ $href }}" @if($active) aria-current="page" @endif
   {{ $attributes->merge(['class' => 'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors '.($active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900')]) }}>
    {{ $slot }}
</a>
