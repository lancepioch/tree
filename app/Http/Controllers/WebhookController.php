<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function githubPullRequest(Request $request)
    {
        $pullRequest = $request->input();

        \Log::info($pullRequest);

        $projects = Project::where('github_repo', $pullRequest->repository->full_name)->get();

        foreach ($projects as $project) {
            dispatch(new App\Jobs\SetupSite($project, $pullRequest));
        }
    }
}
