@props(['title', 'description' => null, 'breadcrumbs' => []])

<div class="mb-6">
    @if(count($breadcrumbs))
        <nav class="flex items-center gap-2 text-xs text-gray-500 mb-2">
            @foreach($breadcrumbs as $crumb)
                @if(!$loop->last)
                    <a href="{{ $crumb['href'] }}" class="hover:text-gray-700">{{ $crumb['label'] }}</a>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                @else
                    <span class="text-gray-700">{{ $crumb['label'] }}</span>
                @endif
            @endforeach
        </nav>
    @endif

    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">{{ $title }}</h1>
            @if($description)
                <p class="mt-0.5 text-sm text-gray-500">{{ $description }}</p>
            @endif
        </div>
        @if(isset($actions))
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
