<?php

namespace App\View\Composers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Projects for the sidebar quick list and the ⌘K command palette.
 */
class NavigationComposer
{
    private ?Collection $projects = null;

    public function __construct(private readonly Request $request) {}

    public function compose(View $view): void
    {
        $view->with('navProjects', $this->projects());
    }

    private function projects(): Collection
    {
        $user = $this->request->user();

        if (! $user) {
            return collect();
        }

        return $this->projects ??= $user->accessibleProjectsQuery()
            ->withHealth()
            ->with('group:id,title')
            ->orderBy('title')
            ->orderBy('id')
            ->get(['projects.id', 'projects.title', 'projects.url', 'projects.group_id', 'projects.uptime_enabled'])
            ->map(fn ($project) => [
                'id' => $project->id,
                'title' => $project->title,
                'host' => $project->url ? (parse_url($project->url, PHP_URL_HOST) ?: $project->url) : null,
                'group' => $project->group?->title,
                'open' => (int) $project->open_count,
                'health' => $project->healthStatus(),
                'url' => route('panel.projects.show', $project),
                'exceptions_url' => route('panel.projects.exceptions.index', $project),
            ]);
    }
}
