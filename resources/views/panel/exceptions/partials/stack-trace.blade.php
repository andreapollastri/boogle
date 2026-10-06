{{-- Stack trace from boogle-client frames ({file, line, class, function, type}); legacy string frames are supported too. --}}
@php
$frames = collect($exception->executor ?? [])
    ->map(fn ($frame) => is_array($frame) ? $frame : ['file' => (string) $frame])
    ->values();

$root = \App\Support\SourcePath::root($frames->pluck('file')->push($exception->file));
$relative = fn (?string $path) => \App\Support\SourcePath::relative($path, $root);
$isVendor = fn (?string $path) => \App\Support\SourcePath::isVendor($path, $root);

// Collapse consecutive vendor frames so the application code stands out.
$groups = [];
foreach ($frames as $index => $frame) {
    $vendor = $isVendor($frame['file'] ?? null);
    $last = array_key_last($groups);
    if ($vendor && $last !== null && $groups[$last]['vendor']) {
        $groups[$last]['frames'][$index] = $frame;
    } else {
        $groups[] = ['vendor' => $vendor, 'frames' => [$index => $frame]];
    }
}
$appFrames = $frames->reject(fn ($f) => $isVendor($f['file'] ?? null))->count();
@endphp

<div class="card overflow-hidden" x-data="{ expandAll: false }">
    <div class="card-header">
        <div>
            <h2 class="card-title">Stack trace</h2>
            <p class="text-xs text-gray-500">
                {{ $frames->count() }} {{ Str::plural('frame', $frames->count()) }} · {{ $appFrames }} in application code
                @if($root) · <span class="font-mono">{{ $root }}</span>@endif
            </p>
        </div>
        @if($frames->count() > $appFrames)
            <button type="button" @click="expandAll = ! expandAll" class="text-xs font-medium text-indigo-600 hover:text-indigo-800"
                    x-text="expandAll ? 'Collapse vendor frames' : 'Expand vendor frames'">Expand vendor frames</button>
        @endif
    </div>

    <ol class="divide-y divide-gray-100 font-mono text-xs">
        @if($exception->file)
            <li class="flex gap-3 bg-red-50/70 px-5 py-3">
                <span class="w-5 shrink-0 pt-px text-right text-[10px] text-red-400">↯</span>
                <div class="min-w-0">
                    <p class="break-all text-red-900"><span class="font-semibold">{{ $relative($exception->file) }}</span><span class="text-red-500">:{{ $exception->line }}</span></p>
                    <p class="mt-0.5 font-sans text-[11px] text-red-600">Thrown here</p>
                </div>
            </li>
        @endif

        @forelse($groups as $group)
            @if($group['vendor'] && count($group['frames']) > 1)
                <li x-data="{ open: false }">
                    <button type="button" @click="open = ! open" x-show="! open && ! expandAll"
                            class="flex w-full items-center gap-3 px-5 py-2 text-left font-sans text-[11px] text-gray-400 hover:bg-gray-50 hover:text-gray-600">
                        <span class="w-5 shrink-0 text-right">⋯</span>
                        {{ count($group['frames']) }} vendor frames
                    </button>
                    <ol x-show="open || expandAll" x-cloak class="divide-y divide-gray-100">
                        @foreach($group['frames'] as $index => $frame)
                            @include('panel.exceptions.partials.frame', ['vendor' => true])
                        @endforeach
                    </ol>
                </li>
            @else
                @foreach($group['frames'] as $index => $frame)
                    @include('panel.exceptions.partials.frame', ['vendor' => $group['vendor']])
                @endforeach
            @endif
        @empty
            <li class="px-5 py-6 text-center font-sans text-xs text-gray-500">No stack frames were reported (fatal errors have none).</li>
        @endforelse
    </ol>
</div>
