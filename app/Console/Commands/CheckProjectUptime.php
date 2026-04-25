<?php

namespace App\Console\Commands;

use App\Models\ExceptionRecord;
use App\Models\ExceptionStatusEvent;
use App\Models\Project;
use App\Models\UptimeCheck;
use App\Notifications\ProjectOfflineAlert;
use App\Notifications\ProjectOnlineAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CheckProjectUptime extends Command
{
    protected $signature = 'projects:check-uptime';

    protected $description = 'Check project URLs uptime and create offline exceptions';

    public function handle(): int
    {
        UptimeCheck::query()
            ->where('checked_at', '<', now()->subDays(45))
            ->delete();

        $expiredByProject = ExceptionRecord::query()
            ->where('exception', 'ProjectOfflineException')
            ->where('created_at', '<', now()->subDays(30))
            ->selectRaw('project_id, COUNT(*) as total')
            ->groupBy('project_id')
            ->pluck('total', 'project_id');

        ExceptionRecord::query()
            ->where('exception', 'ProjectOfflineException')
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        foreach ($expiredByProject as $projectId => $total) {
            Project::query()
                ->where('id', $projectId)
                ->update([
                    'total_exceptions' => DB::raw('GREATEST(total_exceptions - '.(int) $total.', 0)'),
                ]);
        }

        Project::query()
            ->where('uptime_enabled', true)
            ->with('users:id,email,settings')
            ->chunkById(100, function ($projects): void {
                foreach ($projects as $project) {
                    $this->checkProject($project);
                }
            });

        return self::SUCCESS;
    }

    private function checkProject(Project $project): void
    {
        $url = trim((string) ($project->uptime_url ?: $project->url));

        if ($url === '') {
            return;
        }

        $fingerprint = hash('sha256', "uptime|offline|{$url}");
        $isOffline = false;
        $reason = null;
        $httpStatus = null;

        $startedAt = microtime(true);

        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withoutRedirecting()
                ->get($url);

            $httpStatus = $response->status();

            if ($response->serverError() || $response->clientError()) {
                $isOffline = true;
                $reason = "Uptime check failed with HTTP status {$response->status()}.";
            }
        } catch (\Throwable $throwable) {
            $isOffline = true;
            $reason = $throwable->getMessage();
        }

        $responseTimeMs = (int) round((microtime(true) - $startedAt) * 1000);

        UptimeCheck::query()->create([
            'project_id' => $project->id,
            'success' => ! $isOffline,
            'response_time_ms' => $responseTimeMs,
            'http_status' => $httpStatus,
            'checked_at' => now(),
        ]);

        if (! $isOffline) {
            $resolvedExceptions = ExceptionRecord::query()
                ->where('project_id', $project->id)
                ->where('exception', 'ProjectOfflineException')
                ->whereIn('status', [
                    ExceptionRecord::STATUS_OPEN,
                    ExceptionRecord::STATUS_READ,
                    ExceptionRecord::STATUS_FIXED,
                ])
                ->where(function ($query) use ($fingerprint, $url): void {
                    $query->where('fingerprint', $fingerprint)
                        ->orWhere('full_url', $url);
                })
                ->get();

            if ($resolvedExceptions->isEmpty()) {
                return;
            }

            $recoveredAt = now();

            foreach ($resolvedExceptions as $ex) {
                $seconds = max(0, (int) $ex->created_at->diffInSeconds($recoveredAt));
                $human = $ex->created_at->diffForHumans($recoveredAt, true);

                $ex->update([
                    'additional' => array_merge($ex->additional ?? [], [
                        'recovered_at' => $recoveredAt->toIso8601String(),
                        'offline_duration_seconds' => $seconds,
                        'offline_duration_human' => $human,
                    ]),
                ]);
                $ex->refresh();
                $ex->markAs(
                    ExceptionRecord::STATUS_DONE,
                    null,
                    "Service back online; outage lasted {$human}.",
                    true,
                    ExceptionStatusEvent::SOURCE_SYSTEM
                );
            }

            $latestResolved = $resolvedExceptions->sortByDesc('created_at')->first();
            $latestResolved = ExceptionRecord::query()->whereKey($latestResolved->id)->first() ?? $latestResolved;

            foreach ($project->users as $user) {
                if (! ($user->notificationSettings()['mail'] ?? true)) {
                    continue;
                }

                $user->notify(new ProjectOnlineAlert($project, $latestResolved));
            }

            return;
        }

        $alreadyOpen = ExceptionRecord::query()
            ->where('project_id', $project->id)
            ->where('exception', 'ProjectOfflineException')
            ->whereIn('status', [
                ExceptionRecord::STATUS_OPEN,
                ExceptionRecord::STATUS_READ,
                ExceptionRecord::STATUS_FIXED,
            ])
            ->where(function ($query) use ($fingerprint, $url): void {
                $query->where('fingerprint', $fingerprint)
                    ->orWhere('full_url', $url);
            })
            ->exists();

        if ($alreadyOpen) {
            return;
        }

        $project->loadMissing('group');
        $issue = ExceptionRecord::nextIssueForProject($project, true);

        $exception = ExceptionRecord::create(array_merge($issue, [
            'project_id' => $project->id,
            'host' => parse_url($url, PHP_URL_HOST) ?: null,
            'method' => 'GET',
            'full_url' => $url,
            'exception' => 'ProjectOfflineException',
            'fingerprint' => $fingerprint,
            'error' => $reason,
            'file_type' => 'uptime',
            'status' => ExceptionRecord::STATUS_OPEN,
            'additional' => [
                'check' => 'uptime',
                'checked_at' => now()->toISOString(),
            ],
        ]));

        $project->increment('total_exceptions');
        $project->update(['last_error_at' => now()]);

        foreach ($project->users as $user) {
            if (! ($user->notificationSettings()['mail'] ?? true)) {
                continue;
            }

            $user->notify(new ProjectOfflineAlert($project, $exception));
        }
    }
}
