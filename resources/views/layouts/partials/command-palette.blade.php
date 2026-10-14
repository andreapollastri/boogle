@php
$isAdmin = auth()->user()->isAdmin();
$paletteData = [
    'projects' => $navProjects->values(),
    'searchUrl' => route('panel.search'),
    'pages' => array_values(array_filter([
        ['title' => 'Dashboard', 'hint' => 'Overview of every project', 'url' => route('panel.dashboard')],
        ['title' => 'Projects', 'hint' => 'All projects', 'url' => route('panel.projects.index')],
        $isAdmin ? ['title' => 'New project', 'hint' => 'Create a project', 'url' => route('panel.projects.create')] : null,
        $isAdmin ? ['title' => 'Groups', 'hint' => 'Organise projects and issue prefixes', 'url' => route('panel.groups.index')] : null,
        $isAdmin ? ['title' => 'Users', 'hint' => 'Manage access', 'url' => route('panel.users.index')] : null,
        $isAdmin ? ['title' => 'Updates', 'hint' => 'Release, channel, nightly updates', 'url' => route('panel.updates.index')] : null,
        ['title' => 'Profile', 'hint' => 'Account, 2FA, notifications, API tokens', 'url' => route('panel.profile')],
        ['title' => 'API docs', 'hint' => 'OpenAPI reference', 'url' => route('api.docs')],
    ])),
];
@endphp

<script type="application/json" id="palette-data">@json($paletteData)</script>

<div x-data="commandPalette" x-show="open" x-cloak class="fixed inset-0 z-[70] overflow-y-auto p-4 pt-[12vh] sm:p-6 sm:pt-[14vh]"
     role="dialog" aria-modal="true" aria-label="Command palette" @keydown.escape.window="hide()">
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-gray-900/40 backdrop-blur-[2px]" @click="hide()"></div>

    <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-[0.98]" x-transition:enter-end="opacity-100 scale-100"
         class="relative mx-auto max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-900/10">
        <div class="flex items-center gap-3 border-b border-gray-100 px-4">
            <svg class="size-5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input x-ref="input" x-model="query" @input="active = 0" type="text" autocomplete="off" spellcheck="false"
                   @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="go()"
                   placeholder="Jump to a project, page or search an issue code (#BUG42)…"
                   class="h-14 w-full border-0 bg-transparent p-0 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0">
            <span class="kbd">esc</span>
        </div>

        <ul x-ref="list" class="max-h-[60vh] overflow-y-auto p-2" role="listbox">
            <template x-for="(item, index) in results" :key="item.type + item.url">
                <li :data-index="index" role="option" :aria-selected="active === index">
                    <a :href="item.url" @mouseenter="active = index" @click.prevent="go(item)"
                       :class="active === index ? 'bg-indigo-50 text-indigo-900' : 'text-gray-700'"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
                              :class="{
                                'bg-gradient-to-br from-indigo-500 to-violet-600 text-white': item.type === 'project',
                                'bg-red-50 text-red-600': item.type === 'exceptions',
                                'bg-gray-100 text-gray-500': item.type === 'page',
                                'bg-amber-50 text-amber-600': item.type === 'search',
                              }">
                            <template x-if="item.type === 'project'"><span x-text="item.title.charAt(0).toUpperCase()"></span></template>
                            <template x-if="item.type === 'exceptions'"><svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg></template>
                            <template x-if="item.type === 'page'"><svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></template>
                            <template x-if="item.type === 'search'"><svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg></template>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-medium" x-text="item.title"></span>
                            <span class="block truncate text-xs text-gray-500"
                                  x-text="item.type === 'project' ? [item.host, item.group].filter(Boolean).join(' · ') : (item.hint ?? (item.type === 'search' ? 'Across all your projects' : 'Exception log'))"></span>
                        </span>
                        <template x-if="item.type === 'project' && item.health === 'offline'">
                            <span class="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-semibold text-red-700 ring-1 ring-inset ring-red-600/15">Offline</span>
                        </template>
                        <template x-if="(item.type === 'project' || item.type === 'exceptions') && item.open > 0">
                            <span class="rounded-full bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold text-red-700 tabular-nums" x-text="item.open + ' open'"></span>
                        </template>
                    </a>
                </li>
            </template>
            <li x-show="results.length === 0" class="px-3 py-10 text-center text-sm text-gray-500">No matches.</li>
        </ul>

        <div class="flex items-center gap-4 border-t border-gray-100 bg-gray-50 px-4 py-2 text-[11px] text-gray-500">
            <span class="inline-flex items-center gap-1"><span class="kbd">↑</span><span class="kbd">↓</span> navigate</span>
            <span class="inline-flex items-center gap-1"><span class="kbd">↵</span> open</span>
            <span class="ml-auto hidden sm:inline">Tip: <span class="kbd">/</span> focuses search on lists</span>
        </div>
    </div>
</div>
