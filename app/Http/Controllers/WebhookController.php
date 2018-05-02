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
        $signature = $request->header('X-Hub-Signature');

        abort_unless(isset($input['pull_request']), 200, 'Not a Pull Request');
        abort_unless(isset($input['repository']), 200, 'Not a Repository');
        abort_if(is_null($signature), 200, 'Signature Required');

        [$algorithm, $signature] = explode('=', $signature, 2);
        $pullRequest = $input['pull_request'];

        $project = Project::where('github_repo', $input['repository']['full_name'])->with(['branches', 'user'])->first();
        abort_if($project === null, 200, 'Project Not Found');
        $branch = $project->branches()->where('issue_number', $pullRequest['number'])->orderBy('id', 'desc')->first();

        // Signature Verification
        $hash = hash_hmac($algorithm, $request->getContent(), $project->webhook_secret);
        if ($hash !== $signature) {
            return response()->json([
                'error' => 'Signature Verification Failed',
                'hash' => $hash,
                'signature' => $signature,
            ]);
        }

        switch ($input['action'] ?? 'none') {
            case 'opened':
            case 'reopened':
                SetupSite::dispatch($project, $pullRequest);
                break;
            case 'closed':
                RemoveSite::dispatch($branch);
                break;
            case 'synchronize':
                DeploySite::dispatch($branch);
                break;
            case 'assigned':
            case 'unassigned':
            case 'review_requested':
            case 'review_request_removed':
            case 'labeled':
            case 'unlabeled':
            case 'edited':
            default:
        }

        return response()->json(['action' => $input['action']]);
    }
}
