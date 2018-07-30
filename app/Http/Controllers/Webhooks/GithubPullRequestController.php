<?php

namespace App\Http\Controllers\Webhooks;

use App\Jobs\SetupSite;
use App\Jobs\DeploySite;
use App\Jobs\RemoveSite;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptGithubWebhook;

class GithubPullRequestController extends Controller
{
    public function __invoke(AcceptGithubWebhook $request)
    {
        $input = $request->validated();
        $event = $request->header('X-GitHub-Event');

        if ($event === 'ping') {
            return response()->json('ping');
        }

        $pullRequest = $input['pull_request'];

        $branch = $request->project->branches()->where('issue_number', $pullRequest['number'])->orderBy('id', 'desc')->first();

        switch ($input['action'] ?? 'none') {
            case 'opened':
            case 'reopened':
                SetupSite::dispatch($request->project, $pullRequest);
                break;
            case 'closed':
                abort_if(is_null($branch), 400, 'Branch Not Found');
                RemoveSite::dispatch($branch);
                break;
            case 'synchronize':
                abort_if(is_null($branch), 400, 'Branch Not Found');
                DeploySite::dispatch($branch);
                break;
            case 'assigned':
            case 'unassigned':
            case 'review_requested':
            case 'review_request_removed':
            case 'labeled':
            case 'unlabeled':
            case 'edited':
        }

        return response()->json(['action' => $input['action']]);
    }
}
