<?php

namespace App\Console\Commands;

use App\Models\ExceptionRecord;
use Illuminate\Console\Command;

class PruneOldExceptions extends Command
{
    protected $signature = 'exceptions:prune-old';

    protected $description = 'Delete exceptions older than 24 months';

    public function handle(): int
    {
        $deleted = ExceptionRecord::query()
            ->where('created_at', '<', now()->subMonths(24))
            ->delete();

        $this->info("Deleted {$deleted} old exceptions.");

        return self::SUCCESS;
    }
}
