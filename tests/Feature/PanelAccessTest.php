<?php

namespace Tests\Feature;

use App\Models\ExceptionRecord;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/')->assertRedirect('/login');
    }

    public function test_an_admin_can_open_every_panel_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $group = ProjectGroup::factory()->create(['user_id' => $admin->id, 'issue_prefix' => 'OPS']);
        $project = Project::factory()->create(['group_id' => $group->id]);
        $project->users()->attach($admin->id, ['is_owner' => true]);
        $exception = ExceptionRecord::factory()->for($project)->create([
            'executor' => [['file' => '/var/www/app/vendor/laravel/framework/src/Foo.php', 'line' => 3, 'class' => 'Foo', 'function' => 'bar', 'type' => '->']],
            'http' => ['url' => 'https://app.example.com/x?y=1', 'method' => 'GET', 'query' => ['y' => '1'], 'client' => ['browser' => 'Chrome 141', 'os' => 'macOS']],
            'user' => ['id' => 1, 'name' => 'Jane', 'email' => 'jane@example.org'],
            'storage' => ['php' => '8.4', 'laravel' => '13.0'],
        ]);
        ExceptionRecord::factory()->for($project)->outage()->create();

        $this->actingAs($admin);

        foreach ([
            route('panel.dashboard'),
            route('panel.projects.index'),
            route('panel.projects.index', ['group_id' => $group->id]),
            route('panel.projects.create'),
            route('panel.projects.show', $project),
            route('panel.projects.edit', $project),
            route('panel.projects.installation', $project),
            route('panel.projects.exceptions.index', $project),
            route('panel.projects.exceptions.index', [$project, 'status' => 'OPEN', 'kind' => 'outage', 'grouped' => 1]),
            route('panel.projects.exceptions.show', [$project, $exception]),
            route('panel.groups.index'),
            route('panel.users.index'),
            route('panel.profile'),
            route('panel.search', ['q' => 'Exception']),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_the_sidebar_and_palette_list_accessible_projects(): void
    {
        $user = User::factory()->create();
        $mine = Project::factory()->create(['title' => 'Visible Project']);
        $mine->users()->attach($user->id);
        Project::factory()->create(['title' => 'Hidden Project']);

        $this->actingAs($user)
            ->get(route('panel.dashboard'))
            ->assertOk()
            ->assertSee('Visible Project')
            ->assertDontSee('Hidden Project');
    }

    public function test_basic_users_only_reach_their_projects(): void
    {
        $user = User::factory()->create();
        $mine = Project::factory()->create();
        $mine->users()->attach($user->id);
        $other = Project::factory()->create();
        $foreign = ExceptionRecord::factory()->for($other)->create();

        $this->actingAs($user);

        $this->get(route('panel.projects.show', $mine))->assertOk();
        $this->get(route('panel.projects.exceptions.index', $mine))->assertOk();

        $this->get(route('panel.projects.show', $other))->assertForbidden();
        $this->get(route('panel.projects.exceptions.index', $other))->assertForbidden();
        $this->get(route('panel.projects.exceptions.show', [$other, $foreign]))->assertForbidden();

        // Settings, installation and admin sections are for admins only.
        $this->get(route('panel.projects.edit', $mine))->assertForbidden();
        $this->get(route('panel.projects.installation', $mine))->assertForbidden();
        $this->get(route('panel.users.index'))->assertForbidden();
    }

    public function test_group_members_reach_projects_in_their_group(): void
    {
        $user = User::factory()->create();
        $group = ProjectGroup::factory()->create();
        $group->assignedUsers()->attach($user->id);
        $project = Project::factory()->create(['group_id' => $group->id]);

        $this->actingAs($user)->get(route('panel.projects.show', $project))->assertOk();
    }

    public function test_exceptions_from_another_project_are_not_found(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $exception = ExceptionRecord::factory()->create();

        $this->actingAs($admin)
            ->get(route('panel.projects.exceptions.show', [$project, $exception]))
            ->assertNotFound();
    }

    public function test_public_share_pages_hide_the_affected_user(): void
    {
        $exception = ExceptionRecord::factory()->create([
            'user' => ['id' => 9, 'name' => 'Private Person', 'email' => 'private@example.org'],
        ]);
        $exception->publish();

        $this->get(route('exceptions.public', $exception->publish_hash))
            ->assertOk()
            ->assertSee($exception->issue_code)
            ->assertDontSee('private@example.org');

        $exception->unpublish();
        $this->get('/e/whatever')->assertNotFound();
    }
}
