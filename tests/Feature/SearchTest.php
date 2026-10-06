<?php

namespace Tests\Feature;

use App\Models\ExceptionRecord;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_unique_issue_code_jumps_to_the_exception(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $exception = ExceptionRecord::factory()->create(['issue_prefix' => 'SHOP', 'issue_number' => 42]);

        $this->actingAs($user)
            ->get(route('panel.search', ['q' => '#shop42']))
            ->assertRedirect(route('panel.projects.exceptions.show', [$exception->project_id, $exception->id]));
    }

    public function test_results_only_include_accessible_projects(): void
    {
        $user = User::factory()->create();
        $mine = Project::factory()->create();
        $mine->users()->attach($user->id);

        ExceptionRecord::factory()->for($mine)->create(['error' => 'Deadlock found in my project', 'issue_number' => 1]);
        ExceptionRecord::factory()->create(['error' => 'Deadlock found elsewhere', 'issue_number' => 2]);

        $this->actingAs($user)
            ->get(route('panel.search', ['q' => 'Deadlock']))
            ->assertOk()
            ->assertSee('Deadlock found in my project')
            ->assertDontSee('Deadlock found elsewhere');
    }
}
