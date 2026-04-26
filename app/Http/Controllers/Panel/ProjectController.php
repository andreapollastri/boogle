<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ExceptionRecord;
use App\Models\Project;
use App\Models\UptimeCheck;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $selectedGroupId = $user->isAdmin()
            ? ($request->string('group_id')->toString() ?: null)
            : null;
        $visibleGroupIds = $user->visibleGroupsQuery()->pluck('project_groups.id');

        $projects = $user->accessibleProjectsQuery()
            ->when($selectedGroupId, function ($query) use ($selectedGroupId, $visibleGroupIds) {
                if (! $visibleGroupIds->contains($selectedGroupId)) {
                    abort(401);
                }

                $query->where('group_id', $selectedGroupId);
            })
            ->withCount([
                'exceptions as open_count' => fn ($q) => $q->where('status', 'OPEN'),
                'exceptions as total_count',
            ])
            ->with('group')
            ->orderBy('title')
            ->orderBy('id')
            ->get();

        $groups = $user->visibleGroupsQuery()
            ->withCount('projects')
            ->orderBy('title')
            ->orderBy('id')
            ->get();

        return view('panel.projects.index', compact('projects', 'groups', 'selectedGroupId'));
    }

    public function create(Request $request)
    {
        $groups = $request->user()->visibleGroupsQuery()->orderBy('title')->orderBy('id')->get();

        return view('panel.projects.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'uptime_enabled' => $request->boolean('uptime_enabled'),
        ]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:500'],
            'uptime_enabled' => ['required', 'boolean'],
            'uptime_url' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
            'group_id' => ['nullable', 'uuid', 'exists:project_groups,id'],
        ]);

        if (empty($data['uptime_url'])) {
            $data['uptime_url'] = null;
        }

        if (! $request->user()->isAdmin()) {
            $data['group_id'] = null;
        } elseif (! empty($data['group_id']) && ! $request->user()->visibleGroupsQuery()->where('project_groups.id', $data['group_id'])->exists()) {
            abort(401);
        }

        $project = Project::create($data);
        $project->users()->attach($request->user()->id, ['is_owner' => true]);

        return redirect()->route('panel.projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    public function show(Request $request, Project $project)
    {
        $this->authorize($request, $project);

        $project->load([
            'users',
            'group',
            'group.user',
            'group.assignedUsers',
        ]);

        $teamMembers = $project->users
            ->mapWithKeys(function ($user) {
                $user->setAttribute('is_project_owner', (bool) ($user->pivot->is_owner ?? false));
                $user->setAttribute('has_group_access', false);

                return [$user->id => $user];
            });

        if ($project->group) {
            $groupVisibleUsers = collect([$project->group->user])
                ->filter()
                ->merge($project->group->assignedUsers);

            foreach ($groupVisibleUsers as $groupUser) {
                if ($teamMembers->has($groupUser->id)) {
                    $existing = $teamMembers->get($groupUser->id);
                    $existing->setAttribute('has_group_access', true);

                    continue;
                }

                $groupUser->setAttribute('is_project_owner', false);
                $groupUser->setAttribute('has_group_access', true);
                $teamMembers->put($groupUser->id, $groupUser);
            }
        }

        $today = CarbonImmutable::today();
        $fromDate = $today->subDays(29)->startOfDay();

        $checkTotals30d = UptimeCheck::query()
            ->where('project_id', $project->id)
            ->where('checked_at', '>=', $fromDate)
            ->selectRaw(
                'COUNT(*) as total, SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful, '
                .'AVG(CASE WHEN success = 1 THEN response_time_ms END) as avg_response_ms'
            )
            ->first();

        $checkTotal = (int) ($checkTotals30d->total ?? 0);
        $checkSuccessful = (int) ($checkTotals30d->successful ?? 0);
        $avgResponseMs30d = $checkTotals30d->avg_response_ms !== null
            ? (int) round((float) $checkTotals30d->avg_response_ms)
            : null;
        $uptimePercent30d = $checkTotal > 0
            ? round(100 * $checkSuccessful / $checkTotal, 1)
            : null;

        $from3h = CarbonImmutable::now()->subHours(3);
        $checks3h = UptimeCheck::query()
            ->where('project_id', $project->id)
            ->where('checked_at', '>=', $from3h)
            ->get(['checked_at', 'success', 'response_time_ms']);

        // 36 slots × 5 min = 3 hours
        $uptimeSeries = collect(range(0, 35))
            ->map(function (int $i) use ($from3h, $checks3h) {
                $slotStart = $from3h->addMinutes($i * 5);
                $slotEnd = $from3h->addMinutes($i * 5 + 5);
                $inSlot = $checks3h->filter(fn ($c) => $c->checked_at >= $slotStart && $c->checked_at < $slotEnd);
                $total = $inSlot->count();
                $successes = $inSlot->where('success', 1)->count();
                $avgMs = $successes > 0 ? (int) round($inSlot->where('success', 1)->avg('response_time_ms')) : null;

                return [
                    'label' => $slotStart->format('H:i'),
                    'avg_ms' => $avgMs,
                    'uptime_percent' => $total > 0 ? round(100 * $successes / $total, 1) : null,
                    'checks' => $total,
                ];
            });

        $maxAvgMs = max(1, (int) $uptimeSeries->pluck('avg_ms')->filter()->max() ?? 0);

        $dailyStats = UptimeCheck::query()
            ->where('project_id', $project->id)
            ->where('checked_at', '>=', $fromDate)
            ->selectRaw('DATE(checked_at) as day, COUNT(*) as total, SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successes, AVG(CASE WHEN success = 1 THEN response_time_ms END) as avg_ms')
            ->groupByRaw('DATE(checked_at)')
            ->get()
            ->keyBy(fn ($r) => Carbon::parse($r->day)->toDateString());

        $series30d = collect(range(0, 29))
            ->map(function (int $offset) use ($fromDate, $dailyStats) {
                $day = $fromDate->addDays($offset);
                $row = $dailyStats->get($day->toDateString());
                $total = (int) ($row?->total ?? 0);
                $successes = (int) ($row?->successes ?? 0);
                $avgMs = $row?->avg_ms !== null ? (int) round((float) $row->avg_ms) : null;

                return [
                    'label' => $day->format('d/m'),
                    'avg_ms' => $avgMs,
                    'uptime_percent' => $total > 0 ? round(100 * $successes / $total, 1) : null,
                    'checks' => $total,
                ];
            })->values()->all();

        $currentOfflineException = ExceptionRecord::query()
            ->where('project_id', $project->id)
            ->where('exception', 'ProjectOfflineException')
            ->whereIn('status', [
                ExceptionRecord::STATUS_OPEN,
                ExceptionRecord::STATUS_READ,
                ExceptionRecord::STATUS_FIXED,
            ])
            ->latest('created_at')
            ->first();

        $offlineIncidents30d = ExceptionRecord::query()
            ->where('project_id', $project->id)
            ->where('exception', 'ProjectOfflineException')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $latestOutageAt = ExceptionRecord::query()
            ->where('project_id', $project->id)
            ->where('exception', 'ProjectOfflineException')
            ->latest('created_at')
            ->value('created_at');

        return view('panel.projects.show', [
            'project' => $project,
            'teamMembers' => $teamMembers
                ->sortBy(fn ($member) => mb_strtolower($member->name))
                ->values(),
            'uptimeOverview' => [
                'status' => ! $project->uptime_enabled ? 'disabled' : ($currentOfflineException ? 'offline' : 'online'),
                'uptime_percent_30d' => $uptimePercent30d,
                'has_uptime_samples' => $checkTotal > 0,
                'offline_incidents_30d' => $offlineIncidents30d,
                'last_outage_at' => $latestOutageAt,
                'avg_response_ms_30d' => $avgResponseMs30d,
                'series' => $uptimeSeries->values()->all(),
                'series_30d' => $series30d,
                'max_avg_ms' => $maxAvgMs,
                'ping_url' => $project->uptime_url ?: $project->url,
            ],
        ]);
    }

    public function edit(Request $request, Project $project)
    {
        $this->authorize($request, $project);
        abort_unless($request->user()->isAdmin(), 403);
        $groups = $request->user()->visibleGroupsQuery()->orderBy('title')->orderBy('id')->get();

        return view('panel.projects.edit', compact('project', 'groups'));
    }

    public function update(Request $request, Project $project)
    {
        $this->authorize($request, $project);
        abort_unless($request->user()->isAdmin(), 403);

        $request->merge([
            'uptime_enabled' => $request->boolean('uptime_enabled'),
        ]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:500'],
            'uptime_enabled' => ['required', 'boolean'],
            'uptime_url' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
            'group_id' => ['nullable', 'uuid', 'exists:project_groups,id'],
        ]);

        if (empty($data['uptime_url'])) {
            $data['uptime_url'] = null;
        }

        if (! $request->user()->isAdmin()) {
            unset($data['group_id']);
        } elseif (! empty($data['group_id']) && ! $request->user()->visibleGroupsQuery()->where('project_groups.id', $data['group_id'])->exists()) {
            abort(401);
        }

        $project->update($data);

        return redirect()->route('panel.projects.show', $project)
            ->with('success', 'Project updated.');
    }

    public function destroy(Request $request, Project $project)
    {
        $this->authorize($request, $project);
        abort_unless($request->user()->isAdmin(), 403);
        $project->delete();

        return redirect()->route('panel.projects.index')->with('success', 'Project deleted.');
    }

    public function installation(Request $request, Project $project)
    {
        $this->authorize($request, $project);
        abort_unless($request->user()->isAdmin(), 403);

        return view('panel.projects.installation', [
            'project' => $project,
            'api_token' => $project->api_token,
        ]);
    }

    public function regenerateToken(Request $request, Project $project)
    {
        $this->authorize($request, $project);
        abort_unless($request->user()->isAdmin(), 403);
        $project->regenerateApiToken();

        return back()->with('success', 'Project API token regenerated.');
    }

    private function authorize(Request $request, Project $project): void
    {
        abort_unless($request->user()->canAccessProject($project), 401);
    }
}
