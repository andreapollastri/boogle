<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Boogle — Self-hosted Exception Tracker</title>
    <meta name="description" content="Boogle is a self-hosted exception tracker and uptime monitor for your Laravel applications. Open-source, no billing, no limits.">
    <link rel="icon" href="{{ 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y="0.9em" font-size="85" font-family="system-ui,Apple Color Emoji,Segoe UI Emoji,Noto Color Emoji,sans-serif">👾</text></svg>') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-indigo-950 text-white">

    {{-- Nav --}}
    <header class="fixed top-0 inset-x-0 z-50 border-b border-white/10 bg-indigo-950/80 backdrop-blur-md">
        <div class="mx-auto max-w-6xl px-6 h-16 flex items-center justify-between">
            <a href="/" class="inline-flex items-center gap-2.5 group">
                <span class="flex size-9 items-center justify-center rounded-xl bg-white/15 text-xl leading-none shadow-md group-hover:scale-105 transition-transform select-none" role="img" aria-label="Boogle">👾</span>
                <span class="text-lg font-bold tracking-tight">Boogle</span>
            </a>
            <a href="/login" class="inline-flex items-center gap-2 rounded-xl bg-indigo-500 hover:bg-indigo-400 px-5 py-2 text-sm font-semibold transition-colors shadow-lg shadow-indigo-500/30">
                Sign in
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </header>

    {{-- Hero --}}
    <section class="relative pt-32 pb-24 px-6 overflow-hidden">
        {{-- Background glow --}}
        <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[900px] h-[600px] bg-indigo-600/20 rounded-full blur-3xl"></div>
            <div class="absolute top-32 left-1/4 w-64 h-64 bg-violet-600/15 rounded-full blur-3xl"></div>
            <div class="absolute top-20 right-1/4 w-80 h-80 bg-blue-600/10 rounded-full blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-4xl text-center">
            <div class="inline-flex items-center gap-2 rounded-full border border-indigo-500/40 bg-indigo-500/10 px-4 py-1.5 text-sm text-indigo-300 mb-8">
                <span class="inline-block w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                Open-source &amp; self-hosted — no limits, no fees
            </div>

            <h1 class="text-5xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight leading-[1.08] mb-6">
                Catch every bug<br>
                <span class="bg-gradient-to-r from-indigo-400 via-violet-400 to-blue-400 bg-clip-text text-transparent">before your users do</span>
            </h1>

            <p class="text-lg sm:text-xl text-indigo-200/80 max-w-2xl mx-auto mb-10 leading-relaxed">
                Boogle is a self-hosted exception tracker and uptime monitor for your Laravel applications.
                Get real-time alerts, group exceptions by project, and keep your apps healthy — all on your own infrastructure.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/login" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-500 hover:bg-indigo-400 px-8 py-4 text-base font-semibold transition-all shadow-xl shadow-indigo-500/30 hover:shadow-indigo-400/40 hover:-translate-y-0.5">
                    Get started
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
                <a href="https://github.com/andreapollastri/boogle" target="_blank" rel="noopener" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl border border-white/20 hover:border-white/40 bg-white/5 hover:bg-white/10 px-8 py-4 text-base font-semibold transition-all">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/></svg>
                    View on GitHub
                </a>
            </div>
        </div>

        {{-- Fake dashboard preview --}}
        <div class="relative mx-auto max-w-5xl mt-20">
            <div class="rounded-2xl border border-white/10 bg-white/5 backdrop-blur-sm overflow-hidden shadow-2xl shadow-black/40">
                <div class="flex items-center gap-1.5 px-4 py-3 border-b border-white/10">
                    <span class="w-3 h-3 rounded-full bg-red-500/70"></span>
                    <span class="w-3 h-3 rounded-full bg-yellow-500/70"></span>
                    <span class="w-3 h-3 rounded-full bg-green-500/70"></span>
                    <span class="ml-3 text-xs text-white/40 font-mono">boogle.yourapp.com/dashboard</span>
                </div>
                <div class="p-6 grid grid-cols-3 gap-4">
                    <div class="col-span-3 sm:col-span-1 rounded-xl bg-white/5 border border-white/10 p-4">
                        <p class="text-xs text-indigo-300 mb-1">Open exceptions</p>
                        <p class="text-3xl font-bold text-white">24</p>
                        <p class="text-xs text-red-400 mt-1">↑ 3 in the last hour</p>
                    </div>
                    <div class="col-span-3 sm:col-span-1 rounded-xl bg-white/5 border border-white/10 p-4">
                        <p class="text-xs text-indigo-300 mb-1">Projects monitored</p>
                        <p class="text-3xl font-bold text-white">7</p>
                        <p class="text-xs text-green-400 mt-1">All healthy</p>
                    </div>
                    <div class="col-span-3 sm:col-span-1 rounded-xl bg-white/5 border border-white/10 p-4">
                        <p class="text-xs text-indigo-300 mb-1">Uptime checks</p>
                        <p class="text-3xl font-bold text-white">99.9%</p>
                        <p class="text-xs text-green-400 mt-1">All endpoints up</p>
                    </div>
                    <div class="col-span-3 rounded-xl bg-white/5 border border-white/10 divide-y divide-white/5">
                        @foreach([
                            ['OPEN',  'ErrorException: Undefined variable $user', 'api-service', '2m ago'],
                            ['READ',  'QueryException: Deadlock found when trying to get lock', 'web-app', '14m ago'],
                            ['FIXED', 'TypeError: Return value must be of type string', 'worker', '1h ago'],
                        ] as $row)
                        <div class="flex items-center gap-3 px-4 py-3">
                            <span @class([
                                'shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full',
                                'bg-red-500/20 text-red-300'    => $row[0] === 'OPEN',
                                'bg-yellow-500/20 text-yellow-300' => $row[0] === 'READ',
                                'bg-green-500/20 text-green-300' => $row[0] === 'FIXED',
                            ])>{{ $row[0] }}</span>
                            <span class="flex-1 text-sm text-white/70 truncate font-mono">{{ $row[1] }}</span>
                            <span class="shrink-0 text-xs text-white/30">{{ $row[2] }}</span>
                            <span class="shrink-0 text-xs text-white/30">{{ $row[3] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            {{-- Glow under preview --}}
            <div class="absolute -bottom-10 left-1/2 -translate-x-1/2 w-2/3 h-16 bg-indigo-600/30 blur-2xl rounded-full pointer-events-none"></div>
        </div>
    </section>

    {{-- Features --}}
    <section class="py-24 px-6">
        <div class="mx-auto max-w-6xl">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold mb-4">Everything your team needs</h2>
                <p class="text-indigo-300 text-lg max-w-xl mx-auto">Built for developers who want full control over their monitoring stack, without vendor lock-in or monthly bills.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                {{-- Feature 1 --}}
                <div class="group rounded-2xl border border-white/10 bg-white/5 hover:bg-white/8 hover:border-white/20 p-6 transition-all">
                    <div class="mb-4 inline-flex size-12 items-center justify-center rounded-xl bg-indigo-500/20 text-2xl">🐛</div>
                    <h3 class="text-lg font-semibold mb-2">Exception tracking</h3>
                    <p class="text-indigo-300 text-sm leading-relaxed">Capture every unhandled exception from your Laravel apps with full stack traces, context, and request details.</p>
                </div>

                {{-- Feature 2 --}}
                <div class="group rounded-2xl border border-white/10 bg-white/5 hover:bg-white/8 hover:border-white/20 p-6 transition-all">
                    <div class="mb-4 inline-flex size-12 items-center justify-center rounded-xl bg-green-500/20 text-2xl">🟢</div>
                    <h3 class="text-lg font-semibold mb-2">Uptime monitoring</h3>
                    <p class="text-indigo-300 text-sm leading-relaxed">Monitor your HTTP endpoints on a schedule and get notified immediately when something goes down.</p>
                </div>

                {{-- Feature 3 --}}
                <div class="group rounded-2xl border border-white/10 bg-white/5 hover:bg-white/8 hover:border-white/20 p-6 transition-all">
                    <div class="mb-4 inline-flex size-12 items-center justify-center rounded-xl bg-violet-500/20 text-2xl">🔔</div>
                    <h3 class="text-lg font-semibold mb-2">Smart notifications</h3>
                    <p class="text-indigo-300 text-sm leading-relaxed">Get instant email alerts when a new exception is captured or an endpoint goes down — per project, fully configurable.</p>
                </div>

                {{-- Feature 4 --}}
                <div class="group rounded-2xl border border-white/10 bg-white/5 hover:bg-white/8 hover:border-white/20 p-6 transition-all">
                    <div class="mb-4 inline-flex size-12 items-center justify-center rounded-xl bg-blue-500/20 text-2xl">📦</div>
                    <h3 class="text-lg font-semibold mb-2">Laravel package</h3>
                    <p class="text-indigo-300 text-sm leading-relaxed">Install the official Boogle package in your app and start reporting exceptions in minutes with a single API token.</p>
                </div>

                {{-- Feature 5 --}}
                <div class="group rounded-2xl border border-white/10 bg-white/5 hover:bg-white/8 hover:border-white/20 p-6 transition-all">
                    <div class="mb-4 inline-flex size-12 items-center justify-center rounded-xl bg-yellow-500/20 text-2xl">👥</div>
                    <h3 class="text-lg font-semibold mb-2">Team & groups</h3>
                    <p class="text-indigo-300 text-sm leading-relaxed">Invite team members, organise projects into groups, and manage roles with granular access control.</p>
                </div>

                {{-- Feature 6 --}}
                <div class="group rounded-2xl border border-white/10 bg-white/5 hover:bg-white/8 hover:border-white/20 p-6 transition-all">
                    <div class="mb-4 inline-flex size-12 items-center justify-center rounded-xl bg-pink-500/20 text-2xl">🔒</div>
                    <h3 class="text-lg font-semibold mb-2">Self-hosted &amp; private</h3>
                    <p class="text-indigo-300 text-sm leading-relaxed">Your data never leaves your infrastructure. Deploy on any server, use any database, stay in full control.</p>
                </div>

            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="py-20 px-6 border-y border-white/10 bg-white/3">
        <div class="mx-auto max-w-4xl">
            <div class="text-center mb-14">
                <h2 class="text-3xl sm:text-4xl font-bold mb-4">Up and running in minutes</h2>
                <p class="text-indigo-300 text-lg">Deploy once, monitor forever.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 text-center">
                <div>
                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-indigo-500/20 border border-indigo-500/30 text-2xl font-bold text-indigo-300">1</div>
                    <h3 class="font-semibold mb-2">Deploy Boogle</h3>
                    <p class="text-indigo-300 text-sm">Clone the repo, configure your <code class="font-mono bg-white/10 px-1.5 py-0.5 rounded">.env</code>, run the migrations. Done.</p>
                </div>
                <div>
                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-indigo-500/20 border border-indigo-500/30 text-2xl font-bold text-indigo-300">2</div>
                    <h3 class="font-semibold mb-2">Create a project</h3>
                    <p class="text-indigo-300 text-sm">Add your app as a project and grab the API token from the installation guide.</p>
                </div>
                <div>
                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-indigo-500/20 border border-indigo-500/30 text-2xl font-bold text-indigo-300">3</div>
                    <h3 class="font-semibold mb-2">Install the package</h3>
                    <p class="text-indigo-300 text-sm">Add the Boogle client package to your Laravel app and exceptions start flowing in automatically.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="relative py-28 px-6 text-center overflow-hidden">
        <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
            <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[700px] h-64 bg-indigo-600/20 rounded-full blur-3xl"></div>
        </div>
        <div class="relative mx-auto max-w-2xl">
            <p class="text-5xl mb-6 select-none" role="img" aria-label="Boogle">👾</p>
            <h2 class="text-3xl sm:text-4xl font-bold mb-4">Ready to take control?</h2>
            <p class="text-indigo-300 text-lg mb-10">Sign in to your Boogle instance and start catching bugs before your users report them.</p>
            <a href="/login" class="inline-flex items-center gap-2 rounded-2xl bg-indigo-500 hover:bg-indigo-400 px-10 py-4 text-base font-semibold transition-all shadow-xl shadow-indigo-500/30 hover:shadow-indigo-400/40 hover:-translate-y-0.5">
                Sign in to Boogle
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-white/10 py-10 px-6">
        <div class="mx-auto max-w-6xl flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-indigo-400">
            <div class="flex items-center gap-2">
                <span class="text-lg select-none" role="img" aria-label="Boogle">👾</span>
                <span class="font-semibold text-white">Boogle</span>
                <span class="text-white/30">—</span>
                <span>Self-hosted exception tracker</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="https://github.com/andreapollastri/boogle" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-indigo-400 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/></svg>
                    GitHub
                </a>
                <div class="flex items-center gap-1">
                    <span>Made with</span>
                    <span class="text-red-400 mx-0.5">♥</span>
                    <span>by</span>
                    <a href="https://web.ap.it" target="_blank" rel="noopener" class="text-indigo-300 hover:text-white font-medium transition-colors ml-1">Andrea Pollastri</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
