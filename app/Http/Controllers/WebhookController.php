<?php

namespace App\Http\Controllers;

use App\Jobs\DeploySite;
use App\Jobs\RemoveSite;
use App\Jobs\SetupSite;
use App\Project;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function githubPullRequest(Request $request)
    {
        $input = $request->input();

        abort_unless(isset($input['pull_request']), 200, 'Not a Pull Request');
        abort_unless(isset($input['repository']), 200, 'Not a Repository');

        $pullRequest = $input['pull_request'];
        $projects = Project::where('github_repo', $input['repository']['full_name'])->with('branches')->get();

        switch ($input['action'] ?? 'other') {
            case 'opened':
            case 'reopened':
                foreach ($projects as $project)
                    SetupSite::dispatch($project, $pullRequest);
                break;
            case 'closed':
                foreach ($projects as $project)
                    RemoveSite::dispatch($project, $pullRequest);
                break;
            case 'synchronize':
                foreach ($projects as $project)
                    DeploySite::dispatch($project->branches()->last(), $pullRequest);
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
