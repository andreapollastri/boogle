<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectApiController extends Controller
{
    public function index()
    {
        return response()->json(
            Project::query()->with('group:id,title')->orderBy('title')->paginate(25)
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:500'],
            'uptime_enabled' => ['sometimes', 'boolean'],
            'uptime_url' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
            'group_id' => ['nullable', 'uuid', 'exists:project_groups,id'],
        ]);

        if (array_key_exists('uptime_url', $data) && empty($data['uptime_url'])) {
            $data['uptime_url'] = null;
        }

        $project = Project::create($data);

        return response()->json($project->fresh('group:id,title'), 201);
    }

    public function show(Project $project)
    {
        return response()->json(
            $project->load('group:id,title')
        );
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'uptime_enabled' => ['sometimes', 'boolean'],
            'uptime_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'group_id' => ['sometimes', 'nullable', 'uuid', 'exists:project_groups,id'],
        ]);

        if (array_key_exists('uptime_url', $data) && empty($data['uptime_url'])) {
            $data['uptime_url'] = null;
        }

        $project->update($data);

        return response()->json($project->fresh('group:id,title'));
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return response()->json(['status' => 'deleted']);
    }
}
