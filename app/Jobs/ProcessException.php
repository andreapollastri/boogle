<?php

namespace App\Jobs;

use App\Models\ExceptionRecord;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessException implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Project $project,
        private readonly array $payload
    ) {}

    public function handle(): void
    {
        $data = $this->payload;
        $this->project->loadMissing('group');

        $isOutage = ExceptionRecord::isOutageExceptionType($data['exception'] ?? null);
        $issue = ExceptionRecord::nextIssueForProject($this->project, $isOutage);

        $http = $data['http'] ?? null;
        $http = is_array($http) ? $http : null;

        $host = $data['host'] ?? null;
        $method = $data['method'] ?? null;
        $fullUrl = $data['fullUrl'] ?? $data['full_url'] ?? null;

        if (is_array($http)) {
            if ($fullUrl === null && ! empty($http['url'] ?? null)) {
                $fullUrl = $http['url'];
            }
            if ($method === null && ! empty($http['method'] ?? null)) {
                $method = $http['method'];
            }
        }

        if ($host === null && is_string($fullUrl) && $fullUrl !== '') {
            $host = parse_url($fullUrl, PHP_URL_HOST) ?: null;
        }

        ExceptionRecord::create(array_merge($issue, [
            'project_id' => $this->project->id,
            'host' => $host,
            'method' => $method,
            'full_url' => $fullUrl,
            'exception' => $data['exception'] ?? null,
            'fingerprint' => hash('sha256', implode('|', [
                $data['exception'] ?? '',
                $data['error'] ?? '',
                $data['file'] ?? '',
                $data['line'] ?? '',
                $data['class'] ?? '',
            ])),
            'error' => $data['error'] ?? null,
            'line' => $data['line'] ?? null,
            'file' => $data['file'] ?? null,
            'class' => $data['class'] ?? null,
            'file_type' => $data['fileType'] ?? $data['file_type'] ?? 'php',
            'status' => ExceptionRecord::STATUS_OPEN,
            'executor' => $data['executor'] ?? null,
            'storage' => $data['storage'] ?? null,
            'additional' => $data['additional'] ?? null,
            'user' => $data['user'] ?? null,
            'http' => $http,
            'raw_exception' => $data,
        ]));

        $this->project->increment('total_exceptions');
        $this->project->update(['last_error_at' => now()]);
    }
}
