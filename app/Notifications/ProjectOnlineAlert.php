<?php

namespace App\Notifications;

use App\Models\ExceptionRecord;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectOnlineAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Project $project,
        private readonly ExceptionRecord $exception
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $project = $this->project;
        $exception = $this->exception;
        $pingUrl = $project->uptime_url ?: $project->url;
        $url = url('/projects/'.$project->id.'/exceptions/'.$exception->id);
        $code = $exception->issue_code;
        $subject = $code
            ? "[Boogle] 🟢 {$code} — {$project->title} is back online"
            : "[Boogle] 🟢 {$project->title} is back online";

        $additional = $exception->additional ?? [];
        $durationHuman = $additional['offline_duration_human'] ?? null;
        $durationSeconds = $additional['offline_duration_seconds'] ?? null;

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Recovery alert');

        if ($code) {
            $message->line("**Issue:** {$code}");
        }

        $message
            ->line("The project **{$project->title}** is reachable again.")
            ->line("URL: {$pingUrl}");

        if ($durationHuman !== null) {
            $line = "It was offline for **{$durationHuman}**";
            if ($durationSeconds !== null) {
                $line .= " ({$durationSeconds} s).";
            } else {
                $line .= '.';
            }
            $message->line($line);
        }

        return $message->action('View incident', $url);
    }
}
