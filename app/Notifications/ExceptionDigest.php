<?php

namespace App\Notifications;

use App\Models\ExceptionRecord;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExceptionDigest extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Project $project,
        private readonly ExceptionRecord $latestException,
        private readonly int $occurrences
    ) {}

    public function via(object $notifiable): array
    {
        $settings = $notifiable->notificationSettings();

        return ($settings['mail'] ?? true) ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $exception = $this->latestException;
        $project = $this->project;
        $url = url('/projects/'.$project->id.'/exceptions/'.$exception->id);

        $code = $exception->issue_code;
        $subject = $code
            ? "[Boogle] 🪲 {$code} — {$this->occurrences} occurrences in {$project->title}"
            : "[Boogle] 🪲 {$this->occurrences} occurrences in {$project->title}";

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Exception digest');

        if ($code) {
            $message->line("**Issue:** {$code}");
        }

        $message
            ->line("We found this exception **{$this->occurrences}** times since the last notification window.")
            ->line("**{$exception->exception}**")
            ->line($exception->error ?? '');

        if (filled($exception->file) || $exception->line !== null) {
            $file = $exception->file ?? '—';
            $line = $exception->line ?? '—';
            $message->line("File: `{$file}` line {$line}");
        }

        return $message
            ->action('View latest occurrence', $url)
            ->line("Project: {$project->title}");
    }
}
