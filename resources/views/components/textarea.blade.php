@props(['label' => null, 'name', 'rows' => 3, 'hint' => null, 'value' => null])

<div>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1.5">{{ $label }}</label>
    @endif
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 resize-y transition-colors' . ($errors->has($name) ? ' border-red-400' : '')]) }}
    >{{ old($name, $value) }}</textarea>
    @error($name)
        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
    @enderror
    @if($hint && !$errors->has($name))
        <p class="mt-1.5 text-xs text-gray-500">{{ $hint }}</p>
    @endif
</div>
