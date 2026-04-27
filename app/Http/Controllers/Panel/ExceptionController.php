<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ExceptionRecord;
use App\Models\ExceptionStatusEvent;
use App\Models\Project;
use Illuminate\Http\Request;

class ExceptionController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $this->authorize($request, $project);

        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'kind' => ['nullable', 'in:all,outage,bug,group'],
        ], [], [
            'date_from' => 'date from',
            'date_to' => 'date to',
        ]);

        $status = $request->get('status', 'ALL');
        $search = $request->get('search');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $k = $request->get('kind');
        $kind = in_array($k, ['outage', 'bug', 'group'], true) ? $k : 'all';

        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $exceptions = $project->exceptions()
            ->when($status && $status !== 'ALL', fn ($q) => $q->where('status', $status))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->when($kind === 'outage', fn ($q) => $q->where('issue_prefix', 'OUT'))
            ->when($kind === 'bug', fn ($q) => $q->where('issue_prefix', 'BUG'))
            ->when($kind === 'group', fn ($q) => $q->whereNotIn('issue_prefix', ['OUT', 'BUG']))
            ->when($search, function ($q) use ($search) {
                $term = trim($search);
                if (preg_match('/^#?([A-Za-z]+)(\d+)$/', $term, $m)) {
                    $q->where('issue_prefix', strtoupper($m[1]))
                        ->where('issue_number', (int) $m[2]);
                } else {
                    $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
                    $q->where(function ($q) use ($like) {
                        $q->where('exception', 'like', $like)
                            ->orWhere('error', 'like', $like)
                            ->orWhere('file', 'like', $like)
                            ->orWhere('full_url', 'like', $like)
                            ->orWhere('host', 'like', $like)
                            ->orWhere('class', 'like', $like);
                    });
                }
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $counts = [
            'all' => $project->exceptions()->count(),
            'open' => $project->exceptions()->where('status', 'OPEN')->count(),
            'read' => $project->exceptions()->where('status', 'READ')->count(),
            'fixed' => $project->exceptions()->where('status', 'FIXED')->count(),
            'done' => $project->exceptions()->where('status', 'DONE')->count(),
        ];

        $indexQuery = $request->except('page');
        if (in_array($indexQuery['kind'] ?? 'all', ['all', ''], true) || ! isset($indexQuery['kind'])) {
            unset($indexQuery['kind']);
        }
        $indexQuery = array_filter(
            $indexQuery,
            static function ($v, $k) {
                if ($k === 'status' && is_string($v) && $v !== '') {
                    return true;
                }

                return $v !== null && $v !== '';
            },
            ARRAY_FILTER_USE_BOTH
        );

        return view('panel.exceptions.index', compact(
            'project',
            'exceptions',
            'counts',
            'status',
            'search',
            'dateFrom',
            'dateTo',
            'kind',
            'indexQuery'
        ));
    }

    public function show(Request $request, Project $project, ExceptionRecord $exception)
    {
        $this->authorize($request, $project);
        abort_if($exception->project_id !== $project->id, 404);

        $exception->load(['statusEvents.user']);

        if ($exception->status === ExceptionRecord::STATUS_OPEN) {
            $exception->markAs(
                ExceptionRecord::STATUS_READ,
                $request->user(),
                null,
                true,
                ExceptionStatusEvent::SOURCE_VIEW
            );
        }

        $occurrences = ExceptionRecord::where('project_id', $project->id)
            ->where('exception', $exception->exception)
            ->where('id', '!=', $exception->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'status', 'created_at', 'issue_prefix', 'issue_number']);

        return view('panel.exceptions.show', compact('project', 'exception', 'occurrences'));
    }

    public function markAs(Request $request, Project $project, ExceptionRecord $exception)
    {
        $this->authorize($request, $project);
        abort_if($exception->project_id !== $project->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:OPEN,READ,FIXED,DONE'],
            'comment' => ['nullable', 'string', 'max:10000'],
        ]);

        $changed = $exception->markAs(
            $validated['status'],
            $request->user(),
            $validated['comment'] ?? null,
            true,
            ExceptionStatusEvent::SOURCE_PANEL
        );

        if (! $changed) {
            return back()->with('error', 'The exception is already in this state.');
        }

        return back()->with('success', "Status set to {$validated['status']}.");
    }

    public function bulkAction(Request $request, Project $project)
    {
        $this->authorize($request, $project);

        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['uuid'],
            'action' => ['required', 'in:OPEN,READ,FIXED,DONE,delete'],
        ]);

        $query = $project->exceptions()->whereIn('id', $data['ids']);

        if ($data['action'] === 'delete') {
            $deleted = $query->delete();
            if ($deleted > 0) {
                Project::decrementTotalExceptionsBy($project->id, (int) $deleted);
                Project::syncLastErrorAtFromExceptions($project->id);
            }
        } else {
            $target = $data['action'];
            foreach ($query->cursor() as $ex) {
                if ($ex->status === $target) {
                    continue;
                }
                $ex->markAs(
                    $target,
                    $request->user(),
                    null,
                    true,
                    ExceptionStatusEvent::SOURCE_BULK
                );
            }
        }

        return back()->with('success', 'Action applied.');
    }

    public function togglePublish(Request $request, Project $project, ExceptionRecord $exception)
    {
        $this->authorize($request, $project);
        abort_if($exception->project_id !== $project->id, 404);

        if ($exception->isPublished()) {
            $exception->unpublish();

            return back()->with('success', 'Exception is now private.');
        }

        $exception->publish();

        return back()->with('success', 'Exception published publicly.');
    }

    public function publicView(string $hash)
    {
        $exception = ExceptionRecord::where('publish_hash', $hash)->firstOrFail();
        $project = $exception->project;

        return view('public.exception', compact('exception', 'project'));
    }

    private function authorize(Request $request, Project $project): void
    {
        abort_unless($request->user()->canAccessProject($project), 403);
    }
}
