@props([
    'action',
    'title',
    'message' => 'This action is permanent and cannot be undone.',
    'trigger' => 'Delete',
    'confirm' => 'Delete permanently',
    'triggerClass' => 'inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50',
])

<div x-data="{ open: false }" class="contents">
    <button type="button" @click="open = true" class="{{ $triggerClass }}" {{ $attributes->only(['title', 'aria-label']) }}>
        {{ $triggerSlot ?? $trigger }}
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" @keydown.escape.window="open = false" role="dialog" aria-modal="true">
            <div x-show="open" x-transition.opacity class="fixed inset-0 bg-gray-900/40 backdrop-blur-[1px]" @click="open = false"></div>
            <div x-show="open" x-transition class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ $message }}</p>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <x-btn variant="secondary" type="button" @click="open = false">Cancel</x-btn>
                    <form method="POST" action="{{ $action }}">
                        @csrf
                        @method('DELETE')
                        <x-btn variant="danger" type="submit">{{ $confirm }}</x-btn>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
