<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptGithubWebhook;
use App\Jobs\DeploySite;
use App\Jobs\RemoveSite;
use App\Jobs\SetupSite;
use Illuminate\Http\JsonResponse;

class GithubPullRequestController extends Controller
{
    public function __invoke(AcceptGithubWebhook $request): JsonResponse
    {
        $input = $request->validated();
        $event = $request->header('X-GitHub-Event');

        if ($event === 'ping') {
            return response()->json('ping');
        }

        $pr = $input['pull_request'];
        $branch = $request->project->getBranchFromPullRequest($pr['number'], $pr['head']['sha'], $pr['head']['ref'], $pr['head']['repo']['full_name']);

        switch ($input['action'] ?? 'none') {
            case 'opened':
            case 'reopened':
                abort_unless(is_null($request->project->paused_at), 400, 'Project Paused');

                dispatch(new SetupSite($branch));

                break;
            case 'closed':
                dispatch(new RemoveSite($branch));

                break;
            case 'synchronize':
                abort_unless(is_null($request->project->paused_at), 400, 'Project Paused');

                dispatch(new DeploySite($branch));

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
