<?php

namespace Tests\Feature;

use App\Models\ExceptionRecord;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectOfflineAlert;
use App\Notifications\ProjectOnlineAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UptimeCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_outage_is_opened_and_closed_automatically(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $project = Project::factory()->create(['url' => 'https://shop.example.com', 'uptime_url' => 'https://shop.example.com/up']);
        $project->users()->attach($owner->id, ['is_owner' => true]);

        $status = 503;
        Http::fake(['shop.example.com/*' => function () use (&$status) {
            return Http::response('', $status);
        }]);
        $this->artisan('projects:check-uptime')->assertSuccessful();

        $outage = ExceptionRecord::where('exception', 'ProjectOfflineException')->firstOrFail();
        $this->assertSame('#OUT1', $outage->issue_code);
        $this->assertSame('OPEN', $outage->status);
        $this->assertStringContainsString('503', $outage->error);
        $this->assertSame('offline', $project->fresh()->healthStatus());
        Notification::assertSentTo($owner, ProjectOfflineAlert::class);

        // A second failing check does not open a duplicate.
        $this->artisan('projects:check-uptime')->assertSuccessful();
        $this->assertSame(1, ExceptionRecord::count());

        $status = 200;
        $this->artisan('projects:check-uptime')->assertSuccessful();

        $outage->refresh();
        $this->assertSame('DONE', $outage->status);
        $this->assertArrayHasKey('recovered_at', $outage->additional);
        $this->assertSame('online', $project->fresh()->healthStatus());
        $this->assertDatabaseHas('exception_status_events', ['exception_id' => $outage->id, 'to_status' => 'DONE', 'source' => 'system']);
        Notification::assertSentTo($owner, ProjectOnlineAlert::class);
        $this->assertSame(3, $project->uptimeChecks()->count());
    }

    public function test_projects_without_uptime_are_not_pinged(): void
    {
        Http::fake();
        Project::factory()->withoutUptime()->create();

        $this->artisan('projects:check-uptime')->assertSuccessful();

        Http::assertNothingSent();
    }
}
