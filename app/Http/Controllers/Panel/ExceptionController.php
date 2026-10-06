<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ExceptionRecord;
use App\Models\ExceptionStatusEvent;
use App\Models\Project;
use App\Support\ExceptionFilters;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExceptionController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $this->authorize($request, $project);

        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'kind' => ['nullable', 'in:'.implode(',', ExceptionFilters::KINDS)],
            'status' => ['nullable', 'in:'.implode(',', ExceptionFilters::STATUSES)],
        ], [], [
            'date_from' => 'date from',
            'date_to' => 'date to',
        ]);

        $filters = ExceptionFilters::fromRequest($request);
        $request->session()->put(ExceptionFilters::sessionKey($project->id), $filters->toQuery());

        $exceptions = ExceptionFilters::order($filters->apply($project->exceptions()->getQuery()))
            ->paginate(25)
            ->withQueryString();

        // How many times each error on this page has been seen overall.
        $seen = $project->exceptions()
            ->whereIn('fingerprint', $exceptions->pluck('fingerprint')->filter()->unique())
            ->selectRaw('fingerprint, COUNT(*) as total')
            ->groupBy('fingerprint')
            ->pluck('total', 'fingerprint');

        $countsByStatus = $filters->apply($project->exceptions()->getQuery(), withStatus: false)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = ['ALL' => (int) $countsByStatus->sum()];
        foreach (ExceptionRecord::statuses() as $status) {
            $counts[$status] = (int) ($countsByStatus[$status] ?? 0);
        }

        $sameAs = $filters->fingerprint
            ? $project->exceptions()->where('fingerprint', $filters->fingerprint)->latest('created_at')->first()
            : null;

        return view('panel.exceptions.index', compact('project', 'exceptions', 'counts', 'filters', 'seen', 'sameAs'));
    }

    public function show(Request $request, Project $project, ExceptionRecord $exception)
    {
        $this->authorize($request, $project);
        abort_if($exception->project_id !== $project->id, 404);

        if ($exception->status === ExceptionRecord::STATUS_OPEN) {
            $exception->markAs(
                ExceptionRecord::STATUS_READ,
                $request->user(),
                null,
                true,
                ExceptionStatusEvent::SOURCE_VIEW
            );
        }

        // Loaded after the automatic OPEN → READ transition so the history includes it.
        $exception->load(['statusEvents.user']);

        $similar = $exception->fingerprint
            ? $project->exceptions()->where('fingerprint', $exception->fingerprint)
            : $project->exceptions()->where('exception', $exception->exception);

        $similarStats = (clone $similar)
            ->selectRaw('COUNT(*) as total, MIN(created_at) as first_seen, MAX(created_at) as last_seen')
            ->first();

        $occurrences = (clone $similar)
            ->whereKeyNot($exception->id)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'status', 'created_at', 'issue_prefix', 'issue_number']);

        $filters = $this->rememberedFilters($request, $project);
        $neighbours = $this->neighbours($project, $exception, $filters);

        return view('panel.exceptions.show', [
            'project' => $project,
            'exception' => $exception,
            'occurrences' => $occurrences,
            'similar' => [
                'total' => (int) ($similarStats->total ?? 1),
                'first_seen' => $similarStats?->first_seen ? Carbon::parse($similarStats->first_seen) : $exception->created_at,
                'last_seen' => $similarStats?->last_seen ? Carbon::parse($similarStats->last_seen) : $exception->created_at,
            ],
            'filters' => $filters,
            'neighbours' => $neighbours,
        ]);
    }

    public function markAs(Request $request, Project $project, ExceptionRecord $exception)
    {
        $this->authorize($request, $project);
        abort_if($exception->project_id !== $project->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:OPEN,READ,FIXED,DONE'],
            'comment' => ['nullable', 'string', 'max:10000'],
            'next' => ['sometimes', 'boolean'],
        ]);

        $changed = $exception->markAs(
            $validated['status'],
            $request->user(),
            $validated['comment'] ?? null,
            true,
            ExceptionStatusEvent::SOURCE_PANEL
        );

        if ($request->boolean('next')) {
            $neighbours = $this->neighbours($project, $exception, $this->rememberedFilters($request, $project));
            $target = $neighbours['older'] ?? $neighbours['newer'];

            $message = $changed ? "{$exception->issue_code} set to {$validated['status']}." : null;

            return $target
                ? redirect()->route('panel.projects.exceptions.show', [$project, $target])->with('success', $message ?? 'Moved to the next issue.')
                : redirect()->route('panel.projects.exceptions.index', [$project, ...$this->rememberedFilters($request, $project)->toQuery()])
                    ->with('success', ($message ? $message.' ' : '').'That was the last issue in this list.');
        }

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
        $affected = 0;

        if ($data['action'] === 'delete') {
            $affected = $query->delete();
            if ($affected > 0) {
                Project::decrementTotalExceptionsBy($project->id, (int) $affected);
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
                $affected++;
            }
        }

        $label = $data['action'] === 'delete' ? 'deleted' : 'set to '.$data['action'];

        return back()->with('success', trans_choice('{0} No exceptions changed.|{1} 1 exception :label.|[2,*] :count exceptions :label.', $affected, ['label' => $label]));
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

    private function rememberedFilters(Request $request, Project $project): ExceptionFilters
    {
        return ExceptionFilters::fromArray(
            (array) $request->session()->get(ExceptionFilters::sessionKey($project->id), [])
        );
    }

    /**
     * Newer / older exceptions in the last list the user looked at, plus the position in it.
     *
     * @return array{newer: ?ExceptionRecord, older: ?ExceptionRecord, position: ?int, total: int}
     */
    private function neighbours(Project $project, ExceptionRecord $exception, ExceptionFilters $filters): array
    {
        $base = fn (): Builder => $filters->apply($project->exceptions()->getQuery());
        $at = $exception->created_at;

        $after = fn (Builder $q) => $q->where(fn ($q) => $q->where('created_at', '>', $at)
            ->orWhere(fn ($q) => $q->where('created_at', $at)->where('id', '>', $exception->id)));
        $before = fn (Builder $q) => $q->where(fn ($q) => $q->where('created_at', '<', $at)
            ->orWhere(fn ($q) => $q->where('created_at', $at)->where('id', '<', $exception->id)));

        $total = $base()->count();
        $inList = $base()->whereKey($exception->id)->exists();

        return [
            'newer' => ExceptionFilters::order($after($base()), 'asc')->first(['id', 'issue_prefix', 'issue_number']),
            'older' => ExceptionFilters::order($before($base()))->first(['id', 'issue_prefix', 'issue_number']),
            'position' => $inList ? $after($base())->count() + 1 : null,
            'total' => $total,
        ];
    }

    private function authorize(Request $request, Project $project): void
    {
        abort_unless($request->user()->canAccessProject($project), 403);
    }
}
