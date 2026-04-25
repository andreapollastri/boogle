<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ExceptionRecord;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $projectsQuery = $user->accessibleProjectsQuery();

        $projects = $projectsQuery
            ->withCount(['exceptions as open_count' => fn ($q) => $q->where('status', 'OPEN')])
            ->orderByDesc('last_error_at')
            ->get();

        $recentExceptions = ExceptionRecord::whereIn('project_id', $user->accessibleProjectsQuery()->select('projects.id'))
            ->with('project')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('panel.dashboard', [
            'projects' => $projects,
            'recentExceptions' => $recentExceptions,
            'stats' => [
                'total_projects' => $projects->count(),
                'open_exceptions' => $projects->sum('open_count'),
                'total_exceptions' => $projects->sum('total_exceptions'),
            ],
        ]);
    }
}
