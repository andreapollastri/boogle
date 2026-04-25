<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExceptionRecord;
use App\Models\ExceptionStatusEvent;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class ExceptionApiController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'project_id' => ['nullable', 'uuid', 'exists:projects,id'],
            'status' => ['nullable', 'in:OPEN,READ,FIXED,DONE'],
        ]);

        $exceptions = ExceptionRecord::query()
            ->with('project:id,title')
            ->when($data['project_id'] ?? null, fn ($q, $projectId) => $q->where('project_id', $projectId))
            ->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->latest('created_at')
            ->paginate(50);

        return response()->json($exceptions);
    }

    public function byProject(Request $request, Project $project)
    {
        $status = $request->validate([
            'status' => ['nullable', 'in:OPEN,READ,FIXED,DONE'],
        ])['status'] ?? null;

        $exceptions = $project->exceptions()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('created_at')
            ->paginate(50);

        return response()->json($exceptions);
    }

    public function show(Project $project, ExceptionRecord $exception)
    {
        abort_if($exception->project_id !== $project->id, 404);

        return response()->json(
            $exception->makeVisible(['raw_exception'])->load('statusEvents.user')
        );
    }

    public function updateStatus(Request $request, Project $project, ExceptionRecord $exception)
    {
        abort_if($exception->project_id !== $project->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:OPEN,READ,FIXED,DONE'],
            'comment' => ['nullable', 'string', 'max:10000'],
        ]);

        $actor = auth('sanctum')->user();
        abort_unless($actor instanceof User, 401, 'Unauthenticated.');

        $exception->markAs(
            $validated['status'],
            $actor,
            $validated['comment'] ?? null,
            true,
            ExceptionStatusEvent::SOURCE_API
        );

        return response()->json(
            $exception->fresh()->load('statusEvents.user')->makeVisible(['raw_exception'])
        );
    }
}
