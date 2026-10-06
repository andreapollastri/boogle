@foreach(['success' => ['bg-emerald-50 border-emerald-200 text-emerald-800', 'text-emerald-500', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'], 'error' => ['bg-red-50 border-red-200 text-red-800', 'text-red-500', 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z']] as $type => [$box, $icon, $path])
    @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-transition.opacity.duration.300ms
             @if($type === 'success') x-init="setTimeout(() => show = false, 6000)" @endif
             class="mb-5 flex items-center gap-3 rounded-xl border px-4 py-3 text-sm {{ $box }}" role="{{ $type === 'error' ? 'alert' : 'status' }}">
            <svg class="size-5 shrink-0 {{ $icon }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/></svg>
            <span class="flex-1">{{ session($type) }}</span>
            <button type="button" @click="show = false" class="rounded p-0.5 opacity-60 hover:opacity-100" aria-label="Dismiss">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif
@endforeach
