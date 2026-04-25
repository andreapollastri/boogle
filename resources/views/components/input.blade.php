@props(['label' => null, 'name', 'type' => 'text', 'hint' => null, 'required' => false, 'value' => null])

<div>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1.5">
            {{ $label }}@if($required)<span class="text-red-500 ml-0.5">*</span>@endif
        </label>
    @endif

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-colors' . ($errors->has($name) ? ' border-red-400 focus:border-red-500 focus:ring-red-500/20' : '')]) }}
    />

    @error($name)
        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
    @enderror

    @if($hint && !$errors->has($name))
        <p class="mt-1.5 text-xs text-gray-500">{{ $hint }}</p>
    @endif
</div>
