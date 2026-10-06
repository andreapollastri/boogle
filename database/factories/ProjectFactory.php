<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->company(),
            'url' => 'https://'.fake()->unique()->domainWord().'.example.com',
            'uptime_enabled' => true,
            'uptime_url' => null,
            'description' => fake()->sentence(),
        ];
    }

    public function withoutUptime(): static
    {
        return $this->state(fn () => ['uptime_enabled' => false]);
    }
}
