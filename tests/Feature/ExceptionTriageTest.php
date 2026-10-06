<?php

namespace Tests\Feature;

use App\Models\ExceptionRecord;
use App\Models\ExceptionStatusEvent;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExceptionTriageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->project = Project::factory()->create();
        $this->actingAs($this->admin);
    }

    /**
     * @return list<ExceptionRecord> oldest first
     */
    private function exceptions(int $count, array $attributes = []): array
    {
        return collect(range(1, $count))
            ->map(fn (int $i) => ExceptionRecord::factory()->for($this->project)->create(array_merge([
                'issue_number' => 100 + $i,
                'created_at' => now()->subHours($count - $i),
            ], $attributes)))
            ->all();
    }

    public function test_opening_an_exception_marks_it_read_and_shows_it_in_the_history(): void
    {
        [$exception] = $this->exceptions(1);

        $this->get(route('panel.projects.exceptions.show', [$this->project, $exception]))
            ->assertOk()
            ->assertSee('Viewed');

        $this->assertSame(ExceptionRecord::STATUS_READ, $exception->fresh()->status);
        $this->assertDatabaseHas('exception_status_events', [
            'exception_id' => $exception->id,
            'from_status' => 'OPEN',
            'to_status' => 'READ',
            'source' => ExceptionStatusEvent::SOURCE_VIEW,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_the_list_filters_by_status_issue_code_and_kind(): void
    {
        [$first, $second] = $this->exceptions(2);
        $second->update(['status' => 'FIXED']);
        $outage = ExceptionRecord::factory()->for($this->project)->outage()->create(['issue_number' => 1]);

        $this->get(route('panel.projects.exceptions.index', [$this->project, 'status' => 'FIXED']))
            ->assertOk()
            ->assertSee('#BUG102')
            ->assertDontSee('#BUG101');

        $this->get(route('panel.projects.exceptions.index', [$this->project, 'search' => '#bug101']))
            ->assertSee('#BUG101')
            ->assertDontSee('#BUG102');

        $this->get(route('panel.projects.exceptions.index', [$this->project, 'kind' => 'outage']))
            ->assertSee('#OUT1')
            ->assertDontSee('#BUG101');
    }

    public function test_grouped_mode_keeps_only_the_latest_occurrence_of_each_error(): void
    {
        $this->exceptions(3, ['fingerprint' => str_repeat('a', 64)]);

        $this->get(route('panel.projects.exceptions.index', [$this->project, 'grouped' => 1]))
            ->assertOk()
            ->assertSee('#BUG103')
            ->assertDontSee('#BUG101')
            ->assertDontSee('#BUG102')
            ->assertSee('×3');
    }

    public function test_the_detail_page_links_to_newer_and_older_exceptions_of_the_last_list(): void
    {
        [$oldest, $middle, $newest] = $this->exceptions(3);
        $middle->update(['status' => 'DONE']);

        // Remember a list that only contains OPEN exceptions.
        $this->get(route('panel.projects.exceptions.index', [$this->project, 'status' => 'OPEN']))->assertOk();

        $this->get(route('panel.projects.exceptions.show', [$this->project, $newest]))
            ->assertOk()
            ->assertSee(route('panel.projects.exceptions.show', [$this->project, $oldest]), false)
            ->assertDontSee(route('panel.projects.exceptions.show', [$this->project, $middle]).'"', false);
    }

    public function test_updating_the_status_can_jump_to_the_next_exception(): void
    {
        [$oldest, $newest] = $this->exceptions(2);
        $this->get(route('panel.projects.exceptions.index', [$this->project, 'status' => 'OPEN']));

        $this->patch(route('panel.projects.exceptions.mark', [$this->project, $newest]), [
            'status' => 'FIXED',
            'comment' => 'Patched in v2.1',
            'next' => 1,
        ])->assertRedirect(route('panel.projects.exceptions.show', [$this->project, $oldest]));

        $this->assertSame('FIXED', $newest->fresh()->status);
        $this->assertDatabaseHas('exception_status_events', [
            'exception_id' => $newest->id,
            'to_status' => 'FIXED',
            'comment' => 'Patched in v2.1',
            'source' => ExceptionStatusEvent::SOURCE_PANEL,
        ]);

        // Nothing left in the OPEN list: back to it.
        $this->patch(route('panel.projects.exceptions.mark', [$this->project, $oldest]), ['status' => 'DONE', 'next' => 1])
            ->assertRedirect(route('panel.projects.exceptions.index', [$this->project, 'status' => 'OPEN']));
    }

    public function test_bulk_actions_update_and_delete_exceptions(): void
    {
        [$a, $b, $c] = $this->exceptions(3);
        $this->project->update(['total_exceptions' => 3]);

        $this->post(route('panel.projects.exceptions.bulk', $this->project), ['ids' => [$a->id, $b->id], 'action' => 'DONE'])
            ->assertSessionHas('success', '2 exceptions set to DONE.');
        $this->assertSame(['DONE', 'DONE', 'OPEN'], [$a->fresh()->status, $b->fresh()->status, $c->fresh()->status]);
        $this->assertSame(2, ExceptionStatusEvent::where('source', ExceptionStatusEvent::SOURCE_BULK)->count());

        $this->post(route('panel.projects.exceptions.bulk', $this->project), ['ids' => [$a->id, $b->id], 'action' => 'delete'])
            ->assertSessionHas('success', '2 exceptions deleted.');
        $this->assertDatabaseCount('exceptions', 1);
        $this->assertSame(1, $this->project->fresh()->total_exceptions);
        $this->assertEquals($c->created_at->toDateTimeString(), $this->project->fresh()->last_error_at->toDateTimeString());
    }

    public function test_total_exceptions_never_goes_below_zero(): void
    {
        $this->project->update(['total_exceptions' => 1]);

        Project::decrementTotalExceptionsBy($this->project->id, 5);

        $this->assertSame(0, $this->project->fresh()->total_exceptions);
    }

    public function test_publishing_creates_a_public_link(): void
    {
        [$exception] = $this->exceptions(1);

        $this->patch(route('panel.projects.exceptions.publish', [$this->project, $exception]))->assertRedirect();
        $hash = $exception->fresh()->publish_hash;
        $this->assertNotNull($hash);
        $this->get(route('exceptions.public', $hash))->assertOk();

        $this->patch(route('panel.projects.exceptions.publish', [$this->project, $exception]));
        $this->assertNull($exception->fresh()->publish_hash);
    }
}
