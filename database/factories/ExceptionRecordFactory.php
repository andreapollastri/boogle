<?php

namespace Database\Factories;

use App\Models\ExceptionRecord;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExceptionRecord>
 */
class ExceptionRecordFactory extends Factory
{
    protected $model = ExceptionRecord::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $exception = fake()->randomElement([
            'ErrorException',
            'TypeError',
            'RuntimeException',
            'Illuminate\\Database\\QueryException',
        ]);
        $error = fake()->sentence();
        $file = '/var/www/app/Http/Controllers/'.fake()->word().'Controller.php';
        $line = fake()->numberBetween(10, 400);

        return [
            'project_id' => Project::factory(),
            'host' => 'app.example.com',
            'method' => 'GET',
            'full_url' => 'https://app.example.com/'.fake()->slug(2),
            'exception' => $exception,
            'fingerprint' => hash('sha256', implode('|', [$exception, $error, $file, $line, $exception])),
            'error' => $error,
            'line' => $line,
            'file' => $file,
            'class' => $exception,
            'file_type' => 'php',
            'status' => ExceptionRecord::STATUS_OPEN,
            'issue_prefix' => 'BUG',
            'issue_number' => fake()->unique()->numberBetween(1, 1_000_000),
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function outage(): static
    {
        return $this->state(fn () => [
            'exception' => 'ProjectOfflineException',
            'class' => null,
            'file' => null,
            'line' => null,
            'error' => 'Uptime check failed with HTTP status 503.',
            'file_type' => 'uptime',
            'issue_prefix' => 'OUT',
        ]);
    }
}
