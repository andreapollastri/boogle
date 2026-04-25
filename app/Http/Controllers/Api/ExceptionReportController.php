<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessException;
use App\Models\Project;
use Illuminate\Http\Request;

class ExceptionReportController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string'],
            'token' => ['required', 'string'],
            'exception' => ['required', 'array'], // full client payload: http, user, executor, host, method, fullUrl, …
        ]);

        $project = Project::where('key', $data['key'])
            ->where('api_token', $data['token'])
            ->first();

        if (! $project) {
            return response()->json(['error' => 'Invalid project credentials.'], 401);
        }

        ProcessException::dispatch($project, $data['exception']);

        return response()->json(['status' => 'ok']);
    }
}
