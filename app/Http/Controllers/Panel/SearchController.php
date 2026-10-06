<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ExceptionRecord;
use App\Support\ExceptionFilters;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Search across every project the user can access. An issue code with a single match
     * (e.g. "#SHOP42") jumps straight to the exception.
     */
    public function __invoke(Request $request)
    {
        $term = trim($request->string('q')->toString());
        $user = $request->user();

        if ($term === '') {
            return view('panel.search', ['term' => $term, 'projects' => collect(), 'exceptions' => null]);
        }

        $exceptions = ExceptionFilters::applySearch(
            ExceptionRecord::query()->whereIn('project_id', $user->accessibleProjectsQuery()->select('projects.id')),
            $term
        )
            ->with('project:id,title')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $isIssueCode = (bool) preg_match('/^#?[A-Za-z0-9]*[A-Za-z]\d+$/', $term);
        if ($isIssueCode && $exceptions->total() === 1) {
            $match = $exceptions->first();

            return redirect()->route('panel.projects.exceptions.show', [$match->project_id, $match->id]);
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
        $projects = $user->accessibleProjectsQuery()
            ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('url', 'like', $like))
            ->withHealth()
            ->orderBy('title')
            ->limit(10)
            ->get();

        return view('panel.search', compact('term', 'projects', 'exceptions'));
    }
}
