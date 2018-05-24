<?php

namespace App\Http\Controllers;

use App\Project;
use App\Jobs\SetupSite;
use App\Jobs\DeploySite;
use App\Jobs\RemoveSite;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function githubPullRequest(Request $request)
    {
        $input = $request->input();
        $signature = $request->header('X-Hub-Signature');
        $event = $request->header('X-GitHub-Event');

        if ($event === 'ping') {
            return response()->json('ping');
        }

        abort_unless(isset($input['pull_request']), 400, 'Not a Pull Request');
        abort_unless(isset($input['repository']), 400, 'Not a Repository');
        abort_if(is_null($signature) || !str_contains($signature, '='), 400, 'Signature Required');

        [$algorithm, $signature] = explode('=', $signature, 2);
        $pullRequest = $input['pull_request'];

        $project = Project::where('github_repo', $input['repository']['full_name'])->with(['branches', 'user'])->first();
        abort_if($project === null, 400, 'Project Not Found');
        $branch = $project->branches()->where('issue_number', $pullRequest['number'])->orderBy('id', 'desc')->first();

        // Signature Verification
        $hash = hash_hmac($algorithm, $request->getContent(), $project->webhook_secret);
        abort_unless($hash === $signature, 400,
            response()->json([
                'error'     => 'Signature Verification Failed',
                'hash'      => $hash,
                'signature' => $signature,
            ])
        );

        switch ($input['action'] ?? 'none') {
            case 'opened':
            case 'reopened':
                SetupSite::dispatch($project, $pullRequest);
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
