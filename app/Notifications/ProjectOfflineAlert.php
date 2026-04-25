<?php

namespace App\Notifications;

use App\Models\ExceptionRecord;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectOfflineAlert extends Notification implements ShouldQueue
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
        $url = url('/projects/'.$project->id.'/exceptions/'.$exception->id);
        $code = $exception->issue_code;
        $subject = $code
            ? "[Boogle] 🔴 {$code} — {$project->title} is offline"
            : "[Boogle] 🔴 {$project->title} is offline";

        $m = (new MailMessage)
            ->subject($subject)
            ->greeting('Uptime alert');

        if ($code) {
            $m->line("**Issue:** {$code}");
        }

        return $m
            ->line("The project **{$project->title}** appears to be offline.")
            ->line("URL: {$project->url}")
            ->line($exception->error ?? 'The uptime check failed.')
            ->action('View exception', $url);
    }
}
