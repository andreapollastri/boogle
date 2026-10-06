<?php

namespace Database\Seeders;

use App\Models\ExceptionRecord;
use App\Models\ExceptionStatusEvent;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Realistic demo data for local evaluation and documentation screenshots.
 *
 *     php artisan migrate:fresh --seed --seeder=DemoSeeder
 *
 * Every project, person and domain below is fictional (example.com / example.org / example.net,
 * IPs from the documentation ranges). Payloads follow the andreapollastri/boogle-client format.
 */
class DemoSeeder extends Seeder
{
    private CarbonImmutable $now;

    /** @var array<string, User> */
    private array $people = [];

    public function run(): void
    {
        mt_srand(2026);
        $this->now = CarbonImmutable::now()->startOfMinute();

        $this->call(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@users.test')->firstOrFail();
        $this->people['admin'] = $admin;

        foreach ([
            'giulia' => ['Giulia Bianchi', 'giulia@users.test', false],
            'marco' => ['Marco Rossi', 'marco@users.test', false],
            'elena' => ['Elena Conti', 'elena@users.test', true],
            'luca' => ['Luca Ferri', 'luca@users.test', false],
        ] as $key => [$name, $email, $isAdmin]) {
            $this->people[$key] = User::query()->updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'password', 'is_admin' => $isAdmin]
            );
        }

        $groups = [
            'shop' => ProjectGroup::query()->create([
                'user_id' => $admin->id,
                'title' => 'E-commerce',
                'description' => 'Customer-facing storefront and checkout services.',
                'issue_prefix' => 'SHOP',
            ]),
            'ops' => ProjectGroup::query()->create([
                'user_id' => $admin->id,
                'title' => 'Internal tools',
                'description' => 'Back office, billing and internal automations.',
                'issue_prefix' => 'OPS',
            ]),
            'web' => ProjectGroup::query()->create([
                'user_id' => $admin->id,
                'title' => 'Marketing sites',
                'description' => 'Public websites and landing pages.',
                'issue_prefix' => null,
            ]),
        ];

        $groups['shop']->assignedUsers()->sync([$this->people['giulia']->id]);
        $groups['ops']->assignedUsers()->sync([$this->people['marco']->id]);

        $seeded = [];
        foreach ($this->projects() as $definition) {
            $project = Project::query()->create([
                'title' => $definition['title'],
                'url' => $definition['url'],
                'uptime_enabled' => $definition['uptime'],
                'uptime_url' => $definition['uptime_url'] ?? null,
                'description' => $definition['description'],
                'group_id' => isset($definition['group']) ? $groups[$definition['group']]->id : null,
            ]);

            $project->users()->attach($admin->id, ['is_owner' => true]);
            foreach ($definition['members'] ?? [] as $member) {
                $project->users()->attach($this->people[$member]->id, ['is_owner' => false]);
            }

            $seeded[] = [$project->load('group'), $definition];
        }

        // Create every occurrence in chronological order so group-wide issue codes grow with time.
        $occurrences = collect($seeded)
            ->flatMap(fn (array $pair) => $this->occurrences($pair[0], $pair[1]))
            ->sortBy(fn (array $o) => $o['at']->getTimestamp())
            ->values();

        foreach ($occurrences as $occurrence) {
            $occurrence['kind'] === 'outage'
                ? $this->createOutage($occurrence['project'], $occurrence['definition'], $occurrence['template'], $occurrence['at'])
                : $this->createIssue($occurrence['project'], $occurrence['definition'], $occurrence);
        }

        foreach ($seeded as [$project, $definition]) {
            if ($definition['uptime']) {
                $this->seedUptimeChecks($project, $definition['base_ms'], $definition['outages'] ?? []);
            }

            $project->update([
                'total_exceptions' => $project->exceptions()->count(),
                'last_error_at' => $project->exceptions()->max('created_at'),
            ]);
        }

        $admin->createToken('CI release pipeline');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function occurrences(Project $project, array $definition): array
    {
        $occurrences = [];

        foreach ($definition['issues'] ?? [] as $issue) {
            $times = $this->occurrenceTimes($issue['count'], $issue['first_days'], $issue['last_minutes']);
            foreach ($times as $position => $at) {
                $occurrences[] = [
                    'kind' => 'issue',
                    'project' => $project,
                    'definition' => $definition,
                    'template' => $issue,
                    'at' => $at,
                    'is_latest' => $position === count($times) - 1,
                    'from_latest' => count($times) - 1 - $position,
                ];
            }
        }

        foreach ($definition['outages'] ?? [] as $outage) {
            $occurrences[] = [
                'kind' => 'outage',
                'project' => $project,
                'definition' => $definition,
                'template' => $outage,
                'at' => $this->now->subMinutes($outage['started_minutes']),
            ];
        }

        return $occurrences;
    }

    private function createIssue(Project $project, array $definition, array $occurrence): void
    {
        $t = $occurrence['template'];
        $at = $occurrence['at'];
        $root = $definition['root'];
        $host = parse_url($definition['url'], PHP_URL_HOST);

        $status = $t['status'];
        if ($status === ExceptionRecord::STATUS_OPEN && $occurrence['from_latest'] >= 2) {
            $status = ExceptionRecord::STATUS_READ;
        }

        $file = str_starts_with($t['file'], '/') ? $t['file'] : $root.'/'.$t['file'];
        $http = isset($t['path'])
            ? $this->http($t['method'], 'https://'.$host.$t['path'], $t, $occurrence['from_latest'])
            : $this->cliHttp();
        $user = ($t['with_user'] ?? false) ? $this->customer($occurrence['from_latest']) : null;

        $payload = [
            'exception' => $t['exception'],
            'error' => $t['error'],
            'file' => $file,
            'line' => $t['line'],
            'class' => $t['exception'],
            'fileType' => 'php',
            'executor' => $this->frames($root, $t['frames'] ?? []),
            'storage' => [
                'php' => $definition['php'],
                'laravel' => $definition['laravel'],
                'driver' => 'mysql',
                'memory' => $t['memory'] ?? '24MB',
            ],
            'user' => $user,
            'http' => $http,
            'host' => isset($t['path']) ? $host : 'worker-01',
            'method' => $http['method'],
            'fullUrl' => $http['url'],
        ];

        $issue = ExceptionRecord::nextIssueForProject($project, false);

        $record = ExceptionRecord::query()->forceCreate(array_merge($issue, [
            'project_id' => $project->id,
            'host' => $payload['host'],
            'method' => $payload['method'],
            'full_url' => $payload['fullUrl'],
            'exception' => $payload['exception'],
            'fingerprint' => hash('sha256', implode('|', [
                $payload['exception'], $payload['error'], $payload['file'], $payload['line'], $payload['class'],
            ])),
            'error' => $payload['error'],
            'line' => $payload['line'],
            'file' => $payload['file'],
            'class' => $payload['class'],
            'file_type' => 'php',
            'status' => $status,
            'executor' => $payload['executor'],
            'storage' => $payload['storage'],
            'user' => $payload['user'],
            'http' => $payload['http'],
            'raw_exception' => $payload,
            'created_at' => $at,
            'updated_at' => $at,
        ]));

        if (($t['publish'] ?? false) && $occurrence['is_latest']) {
            $record->forceFill(['publish_hash' => Str::random(40), 'published_at' => $at->addHour()])->save();
        }

        $this->seedHistory($record, $status, $at, $occurrence['is_latest'] ? ($t['notes'] ?? []) : [], $t['owner'] ?? 'admin');
    }

    private function createOutage(Project $project, array $definition, array $outage, CarbonImmutable $at): void
    {
        $url = $definition['uptime_url'] ?? $definition['url'];
        $issue = ExceptionRecord::nextIssueForProject($project, true);
        $resolved = $outage['duration_minutes'] !== null;

        $additional = ['check' => 'uptime', 'checked_at' => $at->toIso8601String()];
        if ($resolved) {
            $recoveredAt = $at->addMinutes($outage['duration_minutes']);
            $additional += [
                'recovered_at' => $recoveredAt->toIso8601String(),
                'offline_duration_seconds' => $outage['duration_minutes'] * 60,
                'offline_duration_human' => $at->diffForHumans($recoveredAt, true),
            ];
        }

        $record = ExceptionRecord::query()->forceCreate(array_merge($issue, [
            'project_id' => $project->id,
            'host' => parse_url($url, PHP_URL_HOST),
            'method' => 'GET',
            'full_url' => $url,
            'exception' => 'ProjectOfflineException',
            'fingerprint' => hash('sha256', "uptime|offline|{$url}"),
            'error' => $outage['reason'],
            'file_type' => 'uptime',
            'status' => $resolved ? ExceptionRecord::STATUS_DONE : ExceptionRecord::STATUS_OPEN,
            'additional' => $additional,
            'created_at' => $at,
            'updated_at' => $at,
        ]));

        if (! $resolved) {
            return;
        }

        $viewer = $this->people[$outage['viewer'] ?? 'admin'];
        $this->event($record, $viewer, ExceptionStatusEvent::SOURCE_VIEW, 'OPEN', 'READ', null, $at->addMinutes(2));
        $this->event(
            $record,
            null,
            ExceptionStatusEvent::SOURCE_SYSTEM,
            'READ',
            'DONE',
            'Service back online; outage lasted '.$additional['offline_duration_human'].'.',
            $at->addMinutes($outage['duration_minutes'])
        );
    }

    /**
     * @param  list<string>  $notes
     */
    private function seedHistory(ExceptionRecord $record, string $status, CarbonImmutable $at, array $notes, string $owner): void
    {
        if ($status === ExceptionRecord::STATUS_OPEN) {
            return;
        }

        $actor = $this->people[$owner];
        $this->event($record, $actor, ExceptionStatusEvent::SOURCE_VIEW, 'OPEN', 'READ', null, $at->addMinutes(7));

        if (in_array($status, [ExceptionRecord::STATUS_FIXED, ExceptionRecord::STATUS_DONE], true)) {
            $this->event($record, $actor, ExceptionStatusEvent::SOURCE_PANEL, 'READ', 'FIXED', $notes[0] ?? null, $at->addMinutes(95));
        }

        if ($status === ExceptionRecord::STATUS_DONE) {
            $this->event($record, $this->people['admin'], ExceptionStatusEvent::SOURCE_PANEL, 'FIXED', 'DONE', $notes[1] ?? null, $at->addHours(26));
        }
    }

    private function event(ExceptionRecord $record, ?User $user, string $source, string $from, string $to, ?string $comment, CarbonImmutable $at): void
    {
        $at = $at->greaterThan($this->now) ? $this->now : $at;

        ExceptionStatusEvent::query()->forceCreate([
            'exception_id' => $record->id,
            'user_id' => $user?->id,
            'source' => $source,
            'from_status' => $from,
            'to_status' => $to,
            'comment' => $comment,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    /**
     * @param  list<array{start_minutes?: int, started_minutes: int, duration_minutes: ?int, http_status: ?int, response_ms: int}>  $outages
     */
    private function seedUptimeChecks(Project $project, int $baseMs, array $outages): void
    {
        $windows = array_map(fn (array $o) => [
            'from' => $this->now->subMinutes($o['started_minutes']),
            'to' => $o['duration_minutes'] === null
                ? $this->now->addMinute()
                : $this->now->subMinutes($o['started_minutes'])->addMinutes($o['duration_minutes']),
            'status' => $o['http_status'],
            'ms' => $o['response_ms'],
        ], $outages);

        $rows = [];
        $cursor = $this->now->subDays(30)->startOfDay();

        DB::transaction(function () use (&$rows, &$cursor, $windows, $baseMs, $project): void {
            while ($cursor <= $this->now) {
                $failure = null;
                foreach ($windows as $window) {
                    if ($cursor >= $window['from'] && $cursor < $window['to']) {
                        $failure = $window;
                        break;
                    }
                }

                $hour = (int) $cursor->format('G') + ((int) $cursor->format('i')) / 60;
                $daily = 1 + 0.28 * sin(($hour - 7) / 24 * 2 * M_PI);
                $ms = (int) round($baseMs * $daily + mt_rand(-18, 26) * $baseMs / 120);
                if (mt_rand(1, 400) === 1) {
                    $ms *= mt_rand(3, 6);
                }

                $timestamp = $cursor->toDateTimeString();
                $rows[] = [
                    'project_id' => $project->id,
                    'success' => $failure === null,
                    'response_time_ms' => $failure === null ? max(40, $ms) : $failure['ms'] + mt_rand(0, 6),
                    'http_status' => $failure === null ? 200 : $failure['status'],
                    'checked_at' => $timestamp,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                if (count($rows) >= 500) {
                    DB::table('uptime_checks')->insert($rows);
                    $rows = [];
                }

                $cursor = $cursor->addMinute();
            }

            if ($rows !== []) {
                DB::table('uptime_checks')->insert($rows);
            }
        });
    }

    /**
     * @return list<CarbonImmutable>
     */
    private function occurrenceTimes(int $count, int $firstDays, int $lastMinutes): array
    {
        $last = $this->now->subMinutes($lastMinutes);
        $first = $this->now->subDays($firstDays);
        $span = max(1, (int) $first->diffInMinutes($last));

        $times = [$last];
        for ($i = 1; $i < $count; $i++) {
            // Bias towards recent activity, like a regression that keeps firing.
            $ratio = (mt_rand(0, 1000) / 1000) ** 1.6;
            $times[] = $last->subMinutes((int) round($ratio * $span) + $i);
        }

        sort($times);

        return $times;
    }

    private function http(string $method, string $url, array $template, int $variant): array
    {
        $agents = [
            ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Chrome 141', 'macOS'],
            ['Mozilla/5.0 (iPhone; CPU iPhone OS 19_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.0 Mobile/15E148 Safari/604.1', 'Safari 19.0', 'iOS'],
            ['Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0', 'Firefox 143', 'Windows'],
            ['Mozilla/5.0 (Linux; Android 16; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 'Chrome 141', 'Android'],
        ];
        [$ua, $browser, $os] = $template['agent'] ?? $agents[$variant % count($agents)];
        $isJson = $template['json'] ?? false;
        $query = $template['query'] ?? [];

        return [
            'url' => $url.($query !== [] ? '?'.http_build_query($query) : ''),
            'query' => $query,
            'payload' => $template['payload'] ?? [],
            'cookies' => $isJson ? [] : [
                'XSRF-TOKEN' => '[REDACTED]',
                'laravel_session' => '[REDACTED]',
                'cart_id' => 'c_'.substr(md5((string) $variant), 0, 10),
            ],
            'method' => $method,
            'ip' => ['203.0.113.', '198.51.100.', '192.0.2.'][$variant % 3].(17 + $variant * 7 % 200),
            'user_agent' => $ua,
            'client' => ['browser' => $browser, 'os' => $os],
            'content_type' => $isJson ? 'application/json' : ($method === 'GET' ? null : 'application/x-www-form-urlencoded'),
            'is_json' => $isJson,
            'wants_json' => $isJson,
            'is_ajax' => $template['ajax'] ?? false,
            'is_secure' => true,
            'referer' => $template['referer'] ?? null,
            'headers' => $template['headers'] ?? null,
            'session' => null,
        ];
    }

    private function cliHttp(): array
    {
        return [
            'url' => null, 'query' => [], 'payload' => [], 'cookies' => [], 'method' => null, 'ip' => null,
            'user_agent' => null, 'client' => ['browser' => null, 'os' => null], 'content_type' => null,
            'is_json' => null, 'wants_json' => null, 'is_ajax' => null, 'is_secure' => null, 'referer' => null,
            'headers' => null, 'session' => null,
        ];
    }

    private function customer(int $variant): array
    {
        $customers = [
            [18342, 'Jane Cooper', 'jane.cooper@example.org'],
            [20417, 'Tomás Herrera', 'tomas.h@example.org'],
            [9921, 'Aiko Tanaka', 'aiko.t@example.org'],
            [31188, 'Noah Fischer', 'noah.fischer@example.org'],
        ];
        [$id, $name, $email] = $customers[$variant % count($customers)];

        return ['id' => $id, 'uuid' => null, 'email' => $email, 'name' => $name];
    }

    /**
     * @param  list<array{0: string, 1: int, 2: ?string, 3: string, 4?: string}>  $frames
     */
    private function frames(string $root, array $frames): array
    {
        return array_map(fn (array $f) => [
            'file' => str_starts_with($f[0], '/') ? $f[0] : $root.'/'.$f[0],
            'line' => $f[1],
            'class' => $f[2],
            'function' => $f[3],
            'type' => $f[2] === null ? null : ($f[4] ?? '->'),
        ], $frames);
    }

    /**
     * Typical Laravel HTTP kernel frames below a controller action.
     */
    private function httpTail(string $controller, string $action): array
    {
        return array_slice([
            ['vendor/laravel/framework/src/Illuminate/Routing/ControllerDispatcher.php', 46, $controller, $action],
            ['vendor/laravel/framework/src/Illuminate/Routing/Route.php', 265, 'Illuminate\\Routing\\ControllerDispatcher', 'dispatch'],
            ['vendor/laravel/framework/src/Illuminate/Routing/Route.php', 211, 'Illuminate\\Routing\\Route', 'runController'],
            ['vendor/laravel/framework/src/Illuminate/Routing/Router.php', 822, 'Illuminate\\Routing\\Route', 'run'],
            ['vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php', 180, 'Illuminate\\Routing\\Router', '{closure}'],
            ['vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php', 50, 'Illuminate\\Pipeline\\Pipeline', '{closure}'],
            ['vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php', 219, 'Illuminate\\Routing\\Middleware\\SubstituteBindings', 'handle'],
            ['vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/VerifyCsrfToken.php', 87, 'Illuminate\\Pipeline\\Pipeline', '{closure}'],
            ['vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php', 219, 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken', 'handle'],
            ['vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php', 48, 'Illuminate\\Pipeline\\Pipeline', '{closure}'],
            ['vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php', 219, 'Illuminate\\View\\Middleware\\ShareErrorsFromSession', 'handle'],
            ['vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php', 121, 'Illuminate\\Pipeline\\Pipeline', '{closure}'],
        ], 0, 12);
    }

    private function queueTail(string $job): array
    {
        return [
            ['vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php', 36, $job, 'handle'],
            ['vendor/laravel/framework/src/Illuminate/Container/Util.php', 43, 'Illuminate\\Container\\BoundMethod', '{closure}', '::'],
            ['vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php', 96, 'Illuminate\\Container\\Util', 'unwrapIfClosure', '::'],
            ['vendor/laravel/framework/src/Illuminate/Container/Container.php', 799, 'Illuminate\\Container\\BoundMethod', 'call', '::'],
            ['vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php', 129, 'Illuminate\\Container\\Container', 'call'],
            ['vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php', 180, 'Illuminate\\Bus\\Dispatcher', '{closure}'],
            ['vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php', 134, 'Illuminate\\Pipeline\\Pipeline', 'then'],
            ['vendor/laravel/framework/src/Illuminate/Queue/Jobs/Job.php', 102, 'Illuminate\\Queue\\CallQueuedHandler', 'call'],
            ['vendor/laravel/framework/src/Illuminate/Queue/Worker.php', 487, 'Illuminate\\Queue\\Jobs\\Job', 'fire'],
            ['vendor/laravel/framework/src/Illuminate/Queue/Worker.php', 437, 'Illuminate\\Queue\\Worker', 'process'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function projects(): array
    {
        $shopRoot = '/var/www/storefront/releases/20260930';
        $apiRoot = '/var/www/checkout-api/releases/20261002';
        $boRoot = '/var/www/backoffice/releases/20260921';
        $billingRoot = '/var/www/billing/releases/20260915';
        $webRoot = '/var/www/website/releases/20260811';
        $docsRoot = '/var/www/docs/releases/20260903';

        return [
            [
                'title' => 'Storefront',
                'url' => 'https://shop.example.com',
                'description' => 'Customer storefront: catalogue, cart and checkout (Laravel + Livewire).',
                'group' => 'shop',
                'members' => ['giulia'],
                'uptime' => true,
                'base_ms' => 168,
                'root' => $shopRoot,
                'php' => '8.4.13',
                'laravel' => '13.31.0',
                'outages' => [
                    ['started_minutes' => 17 * 1440 + 312, 'duration_minutes' => 4, 'reason' => 'cURL error 28: Operation timed out after 10002 milliseconds with 0 bytes received (see https://curl.se/libcurl/c/libcurl-errors.html) for https://shop.example.com', 'http_status' => null, 'response_ms' => 10002, 'viewer' => 'giulia'],
                ],
                'issues' => [
                    [
                        'exception' => 'ErrorException',
                        'error' => 'Undefined array key "shipping_address"',
                        'file' => 'app/Http/Controllers/CheckoutController.php',
                        'line' => 142,
                        'method' => 'POST',
                        'path' => '/checkout',
                        'payload' => ['email' => 'jane.cooper@example.org', 'billing_address' => ['line1' => '221B Example Street', 'city' => 'Milano', 'zip' => '20121', 'country' => 'IT'], 'same_as_billing' => '1', 'delivery_slot' => 'next-day'],
                        'referer' => 'https://shop.example.com/cart',
                        'with_user' => true,
                        'count' => 14,
                        'first_days' => 3,
                        'last_minutes' => 6,
                        'status' => 'OPEN',
                        'frames' => array_merge([
                            ['app/Http/Controllers/CheckoutController.php', 142, 'Illuminate\\Foundation\\Bootstrap\\HandleExceptions', 'handleError'],
                        ], array_slice($this->httpTail('App\\Http\\Controllers\\CheckoutController', 'store'), 0, 11)),
                    ],
                    [
                        'exception' => 'Illuminate\\Http\\Client\\ConnectionException',
                        'error' => 'cURL error 28: Operation timed out after 10001 milliseconds with 0 bytes received (see https://curl.se/libcurl/c/libcurl-errors.html) for https://payments.example.net/v1/intents',
                        'file' => 'vendor/laravel/framework/src/Illuminate/Http/Client/PendingRequest.php',
                        'line' => 1645,
                        'method' => 'POST',
                        'path' => '/checkout/pay',
                        'payload' => ['order' => 'ORD-2026-18842', 'method' => 'card', 'save_card' => false],
                        'with_user' => true,
                        'ajax' => true,
                        'json' => true,
                        'count' => 9,
                        'first_days' => 6,
                        'last_minutes' => 41,
                        'status' => 'OPEN',
                        'frames' => array_merge([
                            ['vendor/laravel/framework/src/Illuminate/Http/Client/PendingRequest.php', 1017, 'Illuminate\\Http\\Client\\PendingRequest', 'marshalConnectionException'],
                            ['vendor/laravel/framework/src/Illuminate/Http/Client/PendingRequest.php', 879, 'Illuminate\\Http\\Client\\PendingRequest', 'send'],
                            ['app/Services/Payments/PaymentGateway.php', 58, 'Illuminate\\Http\\Client\\PendingRequest', 'post'],
                            ['app/Http/Controllers/PaymentController.php', 34, 'App\\Services\\Payments\\PaymentGateway', 'createIntent'],
                        ], array_slice($this->httpTail('App\\Http\\Controllers\\PaymentController', 'pay'), 0, 8)),
                    ],
                    [
                        'exception' => 'TypeError',
                        'error' => 'App\\Services\\Pricing\\CouponCalculator::apply(): Argument #1 ($coupon) must be of type App\\Models\\Coupon, null given, called in '.$shopRoot.'/app/Http/Controllers/CartController.php on line 88',
                        'file' => 'app/Services/Pricing/CouponCalculator.php',
                        'line' => 31,
                        'method' => 'POST',
                        'path' => '/cart/coupon',
                        'payload' => ['code' => 'AUTUMN-25'],
                        'referer' => 'https://shop.example.com/cart',
                        'with_user' => true,
                        'ajax' => true,
                        'count' => 6,
                        'first_days' => 9,
                        'last_minutes' => 190,
                        'status' => 'READ',
                        'owner' => 'giulia',
                        'publish' => true,
                        'frames' => array_merge([
                            ['app/Http/Controllers/CartController.php', 88, 'App\\Services\\Pricing\\CouponCalculator', 'apply'],
                        ], array_slice($this->httpTail('App\\Http\\Controllers\\CartController', 'applyCoupon'), 0, 11)),
                    ],
                    [
                        'exception' => 'Illuminate\\View\\ViewException',
                        'error' => 'Attempt to read property "name" on null (View: '.$shopRoot.'/resources/views/products/show.blade.php)',
                        'file' => 'resources/views/products/show.blade.php',
                        'line' => 57,
                        'method' => 'GET',
                        'path' => '/products/linen-shirt-sand',
                        'referer' => 'https://shop.example.com/collections/summer',
                        'count' => 3,
                        'first_days' => 12,
                        'last_minutes' => 4 * 1440 + 80,
                        'status' => 'FIXED',
                        'owner' => 'giulia',
                        'notes' => ['Brand relation can be null for imported products — added null-safe access and a fallback label. Deployed in v2.14.1.'],
                        'frames' => array_merge([
                            ['vendor/laravel/framework/src/Illuminate/View/Engines/PhpEngine.php', 58, 'Illuminate\\View\\Engines\\CompilerEngine', 'handleViewException'],
                            ['vendor/laravel/framework/src/Illuminate/View/Engines/CompilerEngine.php', 76, 'Illuminate\\View\\Engines\\PhpEngine', 'evaluatePath'],
                            ['vendor/laravel/framework/src/Illuminate/View/View.php', 208, 'Illuminate\\View\\Engines\\CompilerEngine', 'get'],
                            ['app/Http/Controllers/ProductController.php', 41, 'Illuminate\\View\\View', 'render'],
                        ], array_slice($this->httpTail('App\\Http\\Controllers\\ProductController', 'show'), 0, 8)),
                    ],
                    [
                        'exception' => 'Error',
                        'error' => 'Call to a member function format() on null',
                        'file' => 'app/Mail/OrderShipped.php',
                        'line' => 37,
                        'count' => 2,
                        'first_days' => 20,
                        'last_minutes' => 15 * 1440,
                        'status' => 'DONE',
                        'notes' => ['shipped_at was not set when the carrier webhook arrived before the label job. Mail now uses the webhook timestamp.', 'No new occurrences for a week — closing.'],
                        'frames' => array_merge([
                            ['app/Mail/OrderShipped.php', 37, 'App\\Mail\\OrderShipped', 'content'],
                        ], $this->queueTail('Illuminate\\Mail\\SendQueuedMailable')),
                    ],
                ],
            ],
            [
                'title' => 'Checkout API',
                'url' => 'https://api.shop.example.com',
                'uptime_url' => 'https://api.shop.example.com/up',
                'description' => 'Order, stock and payment API used by the storefront and mobile apps.',
                'group' => 'shop',
                'members' => ['giulia'],
                'uptime' => true,
                'base_ms' => 74,
                'root' => $apiRoot,
                'php' => '8.4.13',
                'laravel' => '13.31.0',
                'issues' => [
                    [
                        'exception' => 'Illuminate\\Database\\QueryException',
                        'error' => 'SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction (Connection: mysql, SQL: update `stock_items` set `reserved` = `reserved` + 1 where `sku` = TSHIRT-BLK-M)',
                        'file' => 'vendor/laravel/framework/src/Illuminate/Database/Connection.php',
                        'line' => 838,
                        'method' => 'POST',
                        'path' => '/api/v2/orders',
                        'json' => true,
                        'agent' => ['ShopApp/5.12.0 (iOS 19.0; iPhone16,2)', null, 'iOS'],
                        'payload' => ['cart_id' => 'c_77d1e0a9b2', 'items' => [['sku' => 'TSHIRT-BLK-M', 'qty' => 1], ['sku' => 'SOCKS-WHT-41', 'qty' => 2]], 'shipping' => 'standard'],
                        'with_user' => true,
                        'count' => 11,
                        'first_days' => 2,
                        'last_minutes' => 23,
                        'status' => 'OPEN',
                        'frames' => array_merge([
                            ['vendor/laravel/framework/src/Illuminate/Database/Connection.php', 794, 'Illuminate\\Database\\Connection', 'runQueryCallback'],
                            ['vendor/laravel/framework/src/Illuminate/Database/Connection.php', 609, 'Illuminate\\Database\\Connection', 'run'],
                            ['vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php', 3990, 'Illuminate\\Database\\Connection', 'update'],
                            ['app/Actions/ReserveStock.php', 27, 'Illuminate\\Database\\Query\\Builder', 'increment'],
                            ['app/Http/Controllers/Api/OrderController.php', 52, 'App\\Actions\\ReserveStock', 'handle'],
                        ], array_slice($this->httpTail('App\\Http\\Controllers\\Api\\OrderController', 'store'), 0, 7)),
                    ],
                    [
                        'exception' => 'Illuminate\\Queue\\MaxAttemptsExceededException',
                        'error' => 'App\\Jobs\\SyncInventoryJob has been attempted too many times.',
                        'file' => 'vendor/laravel/framework/src/Illuminate/Queue/MaxAttemptsExceededException.php',
                        'line' => 24,
                        'count' => 4,
                        'first_days' => 8,
                        'last_minutes' => 9 * 60,
                        'status' => 'READ',
                        'owner' => 'giulia',
                        'frames' => [
                            ['vendor/laravel/framework/src/Illuminate/Queue/Worker.php', 841, 'Illuminate\\Queue\\MaxAttemptsExceededException', 'forJob', '::'],
                            ['vendor/laravel/framework/src/Illuminate/Queue/Worker.php', 554, 'Illuminate\\Queue\\Worker', 'maxAttemptsExceededException'],
                            ['vendor/laravel/framework/src/Illuminate/Queue/Worker.php', 474, 'Illuminate\\Queue\\Worker', 'markJobAsFailedIfAlreadyExceedsMaxAttempts'],
                            ['vendor/laravel/framework/src/Illuminate/Queue/Worker.php', 437, 'Illuminate\\Queue\\Worker', 'process'],
                            ['vendor/laravel/framework/src/Illuminate/Queue/Worker.php', 201, 'Illuminate\\Queue\\Worker', 'runJob'],
                            ['vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php', 149, 'Illuminate\\Queue\\Worker', 'daemon'],
                            ['vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php', 132, 'Illuminate\\Queue\\Console\\WorkCommand', 'runWorker'],
                        ],
                    ],
                    [
                        'exception' => 'JsonException',
                        'error' => 'Syntax error',
                        'file' => 'app/Http/Controllers/Api/CarrierWebhookController.php',
                        'line' => 54,
                        'method' => 'POST',
                        'path' => '/api/webhooks/carrier',
                        'json' => true,
                        'agent' => ['CarrierHub-Webhooks/2.3', null, null],
                        'headers' => ['host' => 'api.shop.example.com', 'content-type' => 'application/json', 'user-agent' => 'CarrierHub-Webhooks/2.3', 'x-carrier-signature' => '[REDACTED]', 'x-request-id' => 'whk_01J9Z4K2M8Q'],
                        'count' => 5,
                        'first_days' => 14,
                        'last_minutes' => 5 * 1440 + 200,
                        'status' => 'FIXED',
                        'owner' => 'giulia',
                        'notes' => ['Carrier sends a UTF-8 BOM on retries. We now strip it before json_decode (PR #412).'],
                        'frames' => array_merge([
                            ['app/Http/Controllers/Api/CarrierWebhookController.php', 54, null, 'json_decode'],
                        ], array_slice($this->httpTail('App\\Http\\Controllers\\Api\\CarrierWebhookController', '__invoke'), 0, 11)),
                    ],
                ],
            ],
            [
                'title' => 'Back Office',
                'url' => 'https://admin.example.com',
                'description' => 'Internal admin for orders, refunds, customers and reporting.',
                'group' => 'ops',
                'members' => ['marco'],
                'uptime' => true,
                'base_ms' => 236,
                'root' => $boRoot,
                'php' => '8.4.11',
                'laravel' => '13.27.0',
                'outages' => [
                    ['started_minutes' => 23 * 1440 + 640, 'duration_minutes' => 25, 'reason' => 'Uptime check failed with HTTP status 500.', 'http_status' => 500, 'response_ms' => 312, 'viewer' => 'marco'],
                ],
                'issues' => [
                    [
                        'exception' => 'Illuminate\\Contracts\\Filesystem\\FileNotFoundException',
                        'error' => 'File does not exist at path '.$boRoot.'/storage/app/private/invoices/INV-2026-0412.pdf.',
                        'file' => 'vendor/laravel/framework/src/Illuminate/Filesystem/Filesystem.php',
                        'line' => 60,
                        'method' => 'GET',
                        'path' => '/invoices/INV-2026-0412/download',
                        'referer' => 'https://admin.example.com/orders/ORD-2026-17311',
                        'with_user' => true,
                        'count' => 3,
                        'first_days' => 1,
                        'last_minutes' => 72,
                        'status' => 'OPEN',
                        'frames' => array_merge([
                            ['vendor/laravel/framework/src/Illuminate/Filesystem/FilesystemAdapter.php', 290, 'Illuminate\\Filesystem\\Filesystem', 'get'],
                            ['app/Http/Controllers/InvoiceController.php', 63, 'Illuminate\\Filesystem\\FilesystemAdapter', 'download'],
                        ], array_slice($this->httpTail('App\\Http\\Controllers\\InvoiceController', 'download'), 0, 10)),
                    ],
                    [
                        'exception' => 'Symfony\\Component\\ErrorHandler\\Error\\FatalError',
                        'error' => 'Allowed memory size of 268435456 bytes exhausted (tried to allocate 20480 bytes)',
                        'file' => 'app/Exports/OrdersExport.php',
                        'line' => 73,
                        'method' => 'GET',
                        'path' => '/reports/orders/export',
                        'query' => ['from' => '2026-01-01', 'to' => '2026-09-30', 'format' => 'xlsx'],
                        'with_user' => true,
                        'memory' => '256MB',
                        'count' => 2,
                        'first_days' => 10,
                        'last_minutes' => 2 * 1440 + 300,
                        'status' => 'READ',
                        'owner' => 'marco',
                    ],
                ],
            ],
            [
                'title' => 'Billing Service',
                'url' => 'https://billing.example.com',
                'uptime_url' => 'https://billing.example.com/health',
                'description' => 'Invoicing, subscriptions and payment reconciliation.',
                'group' => 'ops',
                'members' => ['marco'],
                'uptime' => true,
                'base_ms' => 121,
                'root' => $billingRoot,
                'php' => '8.3.26',
                'laravel' => '12.44.0',
                'outages' => [
                    ['started_minutes' => 4 * 1440 + 130, 'duration_minutes' => 6, 'reason' => 'Uptime check failed with HTTP status 502.', 'http_status' => 502, 'response_ms' => 88, 'viewer' => 'marco'],
                    ['started_minutes' => 18, 'duration_minutes' => null, 'reason' => 'cURL error 7: Failed to connect to billing.example.com port 443 after 4 ms: Could not connect to server (see https://curl.se/libcurl/c/libcurl-errors.html) for https://billing.example.com/health', 'http_status' => null, 'response_ms' => 4],
                ],
                'issues' => [
                    [
                        'exception' => 'PDOException',
                        'error' => 'SQLSTATE[HY000] [2002] Connection refused',
                        'file' => 'vendor/laravel/framework/src/Illuminate/Database/Connectors/Connector.php',
                        'line' => 66,
                        'method' => 'POST',
                        'path' => '/api/invoices',
                        'json' => true,
                        'agent' => ['GuzzleHttp/7', null, null],
                        'payload' => ['customer_id' => 'cus_4821', 'lines' => [['sku' => 'PLAN-PRO', 'qty' => 1, 'amount' => 4900]], 'currency' => 'EUR'],
                        'count' => 7,
                        'first_days' => 1,
                        'last_minutes' => 19,
                        'status' => 'OPEN',
                        'frames' => [
                            ['vendor/laravel/framework/src/Illuminate/Database/Connectors/Connector.php', 66, 'PDO', '__construct'],
                            ['vendor/laravel/framework/src/Illuminate/Database/Connectors/Connector.php', 44, 'Illuminate\\Database\\Connectors\\Connector', 'createPdoConnection'],
                            ['vendor/laravel/framework/src/Illuminate/Database/Connectors/MySqlConnector.php', 24, 'Illuminate\\Database\\Connectors\\Connector', 'createConnection'],
                            ['vendor/laravel/framework/src/Illuminate/Database/Connectors/ConnectionFactory.php', 185, 'Illuminate\\Database\\Connectors\\MySqlConnector', 'connect'],
                            ['vendor/laravel/framework/src/Illuminate/Database/Connection.php', 1231, 'Illuminate\\Database\\Connectors\\ConnectionFactory', '{closure}'],
                            ['vendor/laravel/framework/src/Illuminate/Database/Connection.php', 1267, 'Illuminate\\Database\\Connection', 'getPdo'],
                            ['vendor/laravel/framework/src/Illuminate/Database/Connection.php', 512, 'Illuminate\\Database\\Connection', 'getReadPdo'],
                            ['app/Http/Controllers/Api/InvoiceController.php', 29, 'Illuminate\\Database\\Eloquent\\Builder', 'firstOrFail'],
                            ['vendor/laravel/framework/src/Illuminate/Routing/ControllerDispatcher.php', 46, 'App\\Http\\Controllers\\Api\\InvoiceController', 'store'],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Company Website',
                'url' => 'https://www.example.com',
                'description' => 'Marketing website, blog and contact forms.',
                'group' => 'web',
                'members' => ['luca'],
                'uptime' => true,
                'base_ms' => 92,
                'root' => $webRoot,
                'php' => '8.4.13',
                'laravel' => '13.31.0',
                'outages' => [
                    ['started_minutes' => 9 * 1440 + 400, 'duration_minutes' => 12, 'reason' => 'Uptime check failed with HTTP status 503.', 'http_status' => 503, 'response_ms' => 41, 'viewer' => 'luca'],
                ],
                'issues' => [
                    [
                        'exception' => 'Symfony\\Component\\Mailer\\Exception\\TransportException',
                        'error' => 'Connection could not be established with host "smtp.example.net:587": stream_socket_client(): Unable to connect to smtp.example.net:587 (Connection timed out)',
                        'file' => 'vendor/symfony/mailer/Transport/Smtp/Stream/SocketStream.php',
                        'line' => 154,
                        'method' => 'POST',
                        'path' => '/contact',
                        'payload' => ['name' => 'Priya Natarajan', 'email' => 'priya@example.org', 'topic' => 'sales', 'message' => 'Hi! Could you share pricing for 25 seats?'],
                        'referer' => 'https://www.example.com/contact',
                        'count' => 3,
                        'first_days' => 11,
                        'last_minutes' => 7 * 1440 + 33,
                        'status' => 'FIXED',
                        'owner' => 'luca',
                        'notes' => ['SMTP relay IP was rotated by the provider. Updated MAIL_HOST and moved contact mail to the queue.'],
                        'frames' => array_merge([
                            ['vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php', 279, 'Symfony\\Component\\Mailer\\Transport\\Smtp\\Stream\\SocketStream', 'initialize'],
                            ['vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php', 211, 'Symfony\\Component\\Mailer\\Transport\\Smtp\\SmtpTransport', 'start'],
                            ['vendor/laravel/framework/src/Illuminate/Mail/Mailer.php', 588, 'Symfony\\Component\\Mailer\\Transport\\AbstractTransport', 'send'],
                            ['app/Http/Controllers/ContactController.php', 31, 'Illuminate\\Mail\\Mailer', 'send'],
                        ], array_slice($this->httpTail('App\\Http\\Controllers\\ContactController', 'store'), 0, 8)),
                    ],
                    [
                        'exception' => 'ErrorException',
                        'error' => 'Undefined variable $posts',
                        'file' => 'resources/views/blog/index.blade.php',
                        'line' => 12,
                        'method' => 'GET',
                        'path' => '/blog',
                        'count' => 1,
                        'first_days' => 26,
                        'last_minutes' => 26 * 1440,
                        'status' => 'DONE',
                        'owner' => 'luca',
                        'notes' => ['Controller returned the view before the query on cache miss.', 'Verified after deploy.'],
                        'frames' => array_merge([
                            ['storage/framework/views/9f3c1d0e5b2a.php', 12, 'Illuminate\\Foundation\\Bootstrap\\HandleExceptions', 'handleError'],
                        ], array_slice($this->httpTail('App\\Http\\Controllers\\BlogController', 'index'), 0, 11)),
                    ],
                ],
            ],
            [
                'title' => 'Docs Portal',
                'url' => 'https://docs.example.com',
                'description' => 'Developer documentation and API reference.',
                'uptime' => false,
                'base_ms' => 0,
                'root' => $docsRoot,
                'php' => '8.4.13',
                'laravel' => '13.31.0',
                'issues' => [
                    [
                        'exception' => 'Illuminate\\Contracts\\Container\\BindingResolutionException',
                        'error' => 'Target class [App\\Search\\SearchEngine] does not exist.',
                        'file' => 'vendor/laravel/framework/src/Illuminate/Container/Container.php',
                        'line' => 1163,
                        'method' => 'GET',
                        'path' => '/search',
                        'query' => ['q' => 'webhooks retry'],
                        'count' => 2,
                        'first_days' => 2,
                        'last_minutes' => 300,
                        'status' => 'OPEN',
                        'frames' => array_merge([
                            ['vendor/laravel/framework/src/Illuminate/Container/Container.php', 991, 'Illuminate\\Container\\Container', 'build'],
                            ['vendor/laravel/framework/src/Illuminate/Foundation/Application.php', 1078, 'Illuminate\\Container\\Container', 'resolve'],
                            ['app/Http/Controllers/SearchController.php', 18, 'Illuminate\\Foundation\\Application', 'make'],
                        ], array_slice($this->httpTail('App\\Http\\Controllers\\SearchController', '__invoke'), 0, 9)),
                    ],
                ],
            ],
        ];
    }
}
