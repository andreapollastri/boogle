<?php

namespace App\Console\Commands;

use App\Models\ExceptionNotificationState;
use App\Models\ExceptionRecord;
use App\Models\User;
use App\Notifications\ExceptionDigest;
use Illuminate\Console\Command;

class SendExceptionDigests extends Command
{
    protected $signature = 'exceptions:send-digests';

    protected $description = 'Send digest notifications for repeated exceptions';

    public function handle(): int
    {
        User::query()
            ->with('projects:id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $settings = $user->notificationSettings();

                    if (! ($settings['mail'] ?? true)) {
                        continue;
                    }

                    $snoozeMinutes = max(1, (int) ($settings['snooze_minutes'] ?? 15));

                    foreach ($user->projects as $project) {
                        $fingerprints = ExceptionRecord::query()
                            ->where('project_id', $project->id)
                            ->whereNotNull('fingerprint')
                            ->where('exception', '!=', 'ProjectOfflineException')
                            ->groupBy('fingerprint')
                            ->pluck('fingerprint');

                        foreach ($fingerprints as $fingerprint) {
                            $state = ExceptionNotificationState::firstOrCreate([
                                'user_id' => $user->id,
                                'project_id' => $project->id,
                                'fingerprint' => $fingerprint,
                            ]);

                            $lastNotifiedAt = $state->last_notified_at;

                            if ($lastNotifiedAt && now()->lt($lastNotifiedAt->copy()->addMinutes($snoozeMinutes))) {
                                continue;
                            }

                            $query = ExceptionRecord::query()
                                ->where('project_id', $project->id)
                                ->where('fingerprint', $fingerprint);

                            if ($lastNotifiedAt) {
                                $query->where('created_at', '>', $lastNotifiedAt);
                            } else {
                                $query->where('created_at', '>=', now()->subMinutes($snoozeMinutes));
                            }

                            $occurrences = $query->count();

                            if ($occurrences === 0) {
                                continue;
                            }

                            $latest = ExceptionRecord::query()
                                ->where('project_id', $project->id)
                                ->where('fingerprint', $fingerprint)
                                ->latest('created_at')
                                ->first();

                            if (! $latest) {
                                continue;
                            }

                            $user->notify(new ExceptionDigest($project, $latest, $occurrences));

                            $state->update([
                                'last_notified_at' => now(),
                            ]);
                        }
                    }
                }
            });

        return self::SUCCESS;
    }
}
