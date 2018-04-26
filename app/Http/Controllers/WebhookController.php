<?php

namespace App\Http\Controllers;

use App\Jobs\SetupSite;
use App\Project;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function githubPullRequest(Request $request)
    {
        $input = $request->input();
        switch ($input['action'] ?? 'other') {
            case 'opened':
                break;
            default:
                abort(200, 'Not Interested');
        }

        $projects = Project::where('github_repo', $input['repository']['full_name'])->get();

        foreach ($projects as $project) {
            dispatch(new SetupSite($project, $input['pull_request']));
        }
    }
}
