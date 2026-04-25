@props(['href', 'active' => false])

<a href="{{ $href }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100' }}">
    {{ $slot }}
</a>
