<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ProjectGroup;
use App\Rules\GroupIssuePrefix;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $groups = $request->user()
            ->visibleGroupsQuery()
            ->withCount('projects')
            ->orderBy('title')
            ->orderBy('id')
            ->get();

        return view('panel.groups.index', compact('groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'issue_prefix' => ['nullable', 'string', new GroupIssuePrefix],
        ]);

        $request->user()->projectGroups()->create($data);

        return back()->with('success', 'Group created.');
    }

    public function update(Request $request, ProjectGroup $group)
    {
        abort_unless($request->user()->isAdmin() || $group->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'issue_prefix' => ['nullable', 'string', new GroupIssuePrefix],
        ]);

        $group->update($data);

        return back()->with('success', 'Group updated.');
    }

    public function destroy(Request $request, ProjectGroup $group)
    {
        abort_unless($request->user()->isAdmin() || $group->user_id === $request->user()->id, 403);
        $group->delete();

        return back()->with('success', 'Group deleted.');
    }
}
