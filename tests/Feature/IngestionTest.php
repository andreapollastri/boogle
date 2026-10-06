<?php

namespace Tests\Feature;

use App\Models\ExceptionRecord;
use App\Models\Project;
use App\Models\ProjectGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngestionTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Project $project, array $overrides = []): array
    {
        return [
            'key' => $project->key,
            'token' => $project->api_token,
            'exception' => array_merge([
                'exception' => 'ErrorException',
                'error' => 'Undefined array key "shipping_address"',
                'file' => '/var/www/shop/app/Http/Controllers/CheckoutController.php',
                'line' => 142,
                'class' => 'ErrorException',
                'fileType' => 'php',
                'executor' => [
                    ['file' => '/var/www/shop/vendor/laravel/framework/src/Illuminate/Routing/Controller.php', 'line' => 54, 'class' => 'App\\Http\\Controllers\\CheckoutController', 'function' => 'store', 'type' => '->'],
                ],
                'storage' => ['php' => '8.4.13', 'laravel' => '13.31.0'],
                'user' => ['id' => 7, 'email' => 'jane@example.org', 'name' => 'Jane'],
                'http' => ['url' => 'https://shop.example.com/checkout', 'method' => 'POST', 'ip' => '203.0.113.7'],
            ], $overrides),
        ];
    }

    public function test_it_stores_a_reported_exception_with_an_issue_code(): void
    {
        $project = Project::factory()->create();

        $this->postJson('/api/log', $this->payload($project))
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $record = ExceptionRecord::firstOrFail();
        $this->assertSame('#BUG1', $record->issue_code);
        $this->assertSame(ExceptionRecord::STATUS_OPEN, $record->status);
        $this->assertSame('shop.example.com', $record->host);
        $this->assertSame('POST', $record->method);
        $this->assertSame('https://shop.example.com/checkout', $record->full_url);
        $this->assertSame('jane@example.org', $record->user['email']);
        $this->assertSame('ErrorException', $record->raw_exception['exception']);
        $this->assertSame(1, $project->fresh()->total_exceptions);
        $this->assertNotNull($project->fresh()->last_error_at);
    }

    public function test_group_prefix_and_counter_are_shared_by_projects_in_the_group(): void
    {
        $group = ProjectGroup::factory()->create(['issue_prefix' => 'shop']);
        $storefront = Project::factory()->create(['group_id' => $group->id]);
        $api = Project::factory()->create(['group_id' => $group->id]);

        $this->postJson('/api/log', $this->payload($storefront))->assertOk();
        $this->postJson('/api/log', $this->payload($api))->assertOk();

        $this->assertSame(['#SHOP1', '#SHOP2'], ExceptionRecord::orderBy('issue_number')->get()->pluck('issue_code')->all());
    }

    public function test_it_rejects_invalid_credentials(): void
    {
        $project = Project::factory()->create();

        $this->postJson('/api/log', array_merge($this->payload($project), ['token' => 'wrong']))
            ->assertUnauthorized();

        $this->assertDatabaseCount('exceptions', 0);
    }

    public function test_it_validates_the_payload(): void
    {
        $this->postJson('/api/log', ['key' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['token', 'exception']);
    }
}
