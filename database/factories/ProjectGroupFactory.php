<?php

namespace Database\Factories;

use App\Models\ProjectGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectGroup>
 */
class ProjectGroupFactory extends Factory
{
    protected $model = ProjectGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'issue_prefix' => null,
        ];
    }
}
