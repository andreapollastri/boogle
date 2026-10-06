<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ExceptionRecord;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $projects = $user->accessibleProjectsQuery()
            ->withHealth()
            ->with('group')
            ->orderByDesc('last_error_at')
            ->get();

        $projectIds = $user->accessibleProjectsQuery()->select('projects.id');

        $recentExceptions = ExceptionRecord::whereIn('project_id', $projectIds)
            ->with('project:id,title')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $from = CarbonImmutable::today()->subDays(13);
        $daily = ExceptionRecord::whereIn('project_id', $projectIds)
            ->where('created_at', '>=', $from)
            ->selectRaw("DATE(created_at) as day, COUNT(*) as total, SUM(CASE WHEN exception = 'ProjectOfflineException' THEN 1 ELSE 0 END) as outages")
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->day)->toDateString());

        $activity = collect(range(0, 13))->map(function (int $offset) use ($from, $daily) {
            $day = $from->addDays($offset);
            $row = $daily->get($day->toDateString());

            return [
                'label' => $day->format('D j M'),
                'short' => $day->format('j'),
                'total' => (int) ($row?->total ?? 0),
                'outages' => (int) ($row?->outages ?? 0),
            ];
        });

        $attention = $projects
            ->filter(fn ($p) => $p->is_offline || $p->unresolved_count > 0)
            ->sortByDesc(fn ($p) => [(int) $p->is_offline, $p->open_count, $p->unresolved_count])
            ->values();

        return view('panel.dashboard', [
            'projects' => $projects,
            'attention' => $attention,
            'recentExceptions' => $recentExceptions,
            'activity' => $activity,
            'stats' => [
                'total_projects' => $projects->count(),
                'open_exceptions' => (int) $projects->sum('open_count'),
                'offline_projects' => $projects->where('is_offline', true)->count(),
                'last_24h' => ExceptionRecord::whereIn('project_id', $projectIds)
                    ->where('created_at', '>=', now()->subDay())
                    ->count(),
            ],
        ]);
    }
}
