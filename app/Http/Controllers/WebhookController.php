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
        $signature = $request->header('x-hub-signature');

        abort_unless(isset($input['pull_request']), 200, 'Not a Pull Request');
        abort_unless(isset($input['repository']), 200, 'Not a Repository');
        abort_if(is_null($signature), 200, 'Signature Required');

        $signature = str_replace('sha1=', '', $signature);
        $pullRequest = $input['pull_request'];
        $projects = Project::where('github_repo', $input['repository']['full_name'])
            ->with(['branches', 'user'])
            ->get();

        $errors = [];

        foreach ($projects as $project) {
            // Signature Verification
            if (sha1($project->webhook_secret) !== $signature) {
                $errors[] = [
                    'user' => $project->user->email,
                    'message' => 'Signature Verification Failed',
                ];

                continue;
            }

            switch ($input['action'] ?? 'other') {
                case 'opened':
                case 'reopened':
                    SetupSite::dispatch($project, $pullRequest);
                    break;
                case 'closed':
                    RemoveSite::dispatch($project, $pullRequest);
                    break;
                case 'synchronize':
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
            }
        }

        $response = ['action' => $input['action']];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response);
    }
}
