<?php

namespace App\Http\Controllers;

use App\Jobs\RemoveSite;
use App\Jobs\SetupSite;
use App\Project;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function githubPullRequest(Request $request)
    {
        $input = $request->input();
        $projects = Project::where('github_repo', $input['repository']['full_name'])->get();

        switch ($input['action'] ?? 'other') {
            case 'opened':
            case 'reopened':
                foreach ($projects as $project)
                    dispatch(new SetupSite($project, $input['pull_request']));
                break;
            case 'closed':
                foreach ($projects as $project)
                    dispatch(new RemoveSite($project, $input['pull_request']));
                break;
            case 'assigned':
            case 'unassigned':
            case 'review_requested':
            case 'review_request_removed':
            case 'labeled':
            case 'unlabeled':
            case 'edited':
            default:
                abort(200, 'Not Interested');
        }
    }
}
