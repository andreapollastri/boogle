<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectGroup;
use App\Rules\GroupIssuePrefix;
use Illuminate\Http\Request;

class GroupApiController extends Controller
{
    public function index()
    {
        return response()->json(
            ProjectGroup::query()->withCount('projects')->orderBy('title')->paginate(25)
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'issue_prefix' => ['nullable', 'string', new GroupIssuePrefix],
        ]);

        $group = ProjectGroup::create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        return response()->json($group, 201);
    }

    public function show(ProjectGroup $group)
    {
        return response()->json($group->loadCount('projects'));
    }

    public function update(Request $request, ProjectGroup $group)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'issue_prefix' => ['nullable', 'string', new GroupIssuePrefix],
        ]);

        $group->update($data);

        return response()->json($group->fresh()->loadCount('projects'));
    }

    public function destroy(ProjectGroup $group)
    {
        $group->delete();

        return response()->json(['status' => 'deleted']);
    }
}
