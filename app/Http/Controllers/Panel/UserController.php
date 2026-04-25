<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->withCount(['projects', 'assignedGroups'])
            ->orderByDesc('is_admin')
            ->orderBy('name')
            ->get();

        return view('panel.users.index', compact('users'));
    }

    public function create()
    {
        $proposedPassword = Str::password(16, letters: true, numbers: true, symbols: true, spaces: false);

        return view('panel.users.create', [
            'projects' => Project::query()->orderBy('title')->get(),
            'groups' => ProjectGroup::query()->orderBy('title')->get(),
            'proposedPassword' => $proposedPassword,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults(), 'confirmed'],
            'role' => ['required', 'in:admin,basic'],
            'project_ids' => ['array'],
            'project_ids.*' => ['uuid', 'exists:projects,id'],
            'group_ids' => ['array'],
            'group_ids.*' => ['uuid', 'exists:project_groups,id'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_admin' => $data['role'] === 'admin',
        ]);

        $projectIds = collect($data['project_ids'] ?? [])->unique()->values();
        if ($projectIds->isNotEmpty()) {
            $user->projects()->sync($projectIds->mapWithKeys(fn (string $id) => [$id => ['is_owner' => false]])->all());
        }

        $user->assignedGroups()->sync($data['group_ids'] ?? []);

        return redirect()->route('panel.users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        return view('panel.users.edit', [
            'userModel' => $user->load('projects:id', 'assignedGroups:id'),
            'projects' => Project::query()->orderBy('title')->get(),
            'groups' => ProjectGroup::query()->orderBy('title')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['required', 'in:admin,basic'],
            'manage_assignments' => ['sometimes', 'boolean'],
            'project_ids' => ['sometimes', 'array'],
            'project_ids.*' => ['uuid', 'exists:projects,id'],
            'group_ids' => ['sometimes', 'array'],
            'group_ids.*' => ['uuid', 'exists:project_groups,id'],
        ]);

        if ($request->user()->id === $user->id && $data['role'] !== 'admin') {
            return back()->with('error', 'You cannot remove your own admin role.');
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'is_admin' => $data['role'] === 'admin',
        ];

        $user->update($payload);

        if (! empty($data['manage_assignments'])) {
            $projectIds = collect($data['project_ids'] ?? [])->unique()->values();
            $user->projects()->sync($projectIds->mapWithKeys(fn (string $id) => [$id => ['is_owner' => false]])->all());
            $user->assignedGroups()->sync($data['group_ids'] ?? []);
        }

        return redirect()->route('panel.users.index')->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->projects()->detach();
        $user->assignedGroups()->detach();
        $user->delete();

        return redirect()->route('panel.users.index')->with('success', 'User deleted.');
    }
}
