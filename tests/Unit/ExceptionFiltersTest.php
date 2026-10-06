<?php

namespace Tests\Unit;

use App\Support\ExceptionFilters;
use App\Support\SourcePath;
use PHPUnit\Framework\TestCase;

class ExceptionFiltersTest extends TestCase
{
    public function test_invalid_values_fall_back_to_defaults(): void
    {
        $filters = ExceptionFilters::fromArray([
            'status' => 'nope',
            'kind' => 'weird',
            'date_from' => 'not a date',
            'fingerprint' => 'short',
            'search' => '   ',
        ]);

        $this->assertSame('ALL', $filters->status);
        $this->assertSame('all', $filters->kind);
        $this->assertNull($filters->dateFrom);
        $this->assertNull($filters->fingerprint);
        $this->assertNull($filters->search);
        $this->assertSame([], $filters->toQuery());
        $this->assertFalse($filters->isNarrowed());
    }

    public function test_dates_are_swapped_and_query_keeps_only_non_defaults(): void
    {
        $filters = ExceptionFilters::fromArray([
            'status' => 'open',
            'date_from' => '2026-10-05',
            'date_to' => '2026-10-01',
            'grouped' => '1',
        ]);

        $this->assertSame([
            'status' => 'OPEN',
            'date_from' => '2026-10-01',
            'date_to' => '2026-10-05',
            'grouped' => '1',
        ], $filters->toQuery());
        $this->assertTrue($filters->isNarrowed());
        $this->assertFalse(ExceptionFilters::fromArray(['status' => 'OPEN'])->isNarrowed());
    }

    public function test_source_paths_are_shown_relative_to_the_deploy_root(): void
    {
        $root = SourcePath::root([
            null,
            '/var/www/shop/releases/42/app/Http/Controllers/CartController.php',
            '/var/www/shop/releases/42/vendor/laravel/framework/src/Illuminate/Routing/Route.php',
        ]);

        $this->assertSame('/var/www/shop/releases/42', $root);
        $this->assertSame('app/Http/Controllers/CartController.php', SourcePath::relative('/var/www/shop/releases/42/app/Http/Controllers/CartController.php', $root));
        $this->assertTrue(SourcePath::isVendor('/var/www/shop/releases/42/vendor/foo/Bar.php', $root));
        $this->assertFalse(SourcePath::isVendor('/var/www/shop/releases/42/app/Foo.php', $root));
        $this->assertSame('/elsewhere/file.php', SourcePath::relative('/elsewhere/file.php', $root));
        $this->assertNull(SourcePath::root(['/app/Http/Kernel.php']));
    }
}
