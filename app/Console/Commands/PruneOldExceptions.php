<?php

namespace App\Console\Commands;

use App\Models\ExceptionRecord;
use App\Models\Project;
use Illuminate\Console\Command;

class PruneOldExceptions extends Command
{
    protected $signature = 'exceptions:prune-old';

    protected $description = 'Delete exceptions older than 24 months';

    public function handle(): int
    {
        $query = ExceptionRecord::query()
            ->where('created_at', '<', now()->subMonths(24));

        $byProject = (clone $query)
            ->selectRaw('project_id, COUNT(*) as c')
            ->groupBy('project_id')
            ->pluck('c', 'project_id');

        $deleted = $query->delete();

        foreach ($byProject as $projectId => $count) {
            $id = (string) $projectId;
            Project::decrementTotalExceptionsBy($id, (int) $count);
            Project::syncLastErrorAtFromExceptions($id);
        }

        $this->info("Deleted {$deleted} old exceptions.");

        return self::SUCCESS;
    }
}
