<?php

namespace App\Http\Controllers;

use Github\Client;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Creates a new project.
     *
     * @param Request $request
     * @param Client $github
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, Client $github)
    {
        $input = $request->all() + ['webhook_secret' => str_random(20)];
        $project = auth()->user()->projects()->create($input);

        $github->authenticate($project->user->github_token, null, Client::AUTH_HTTP_PASSWORD);
        [$githubUser, $githubRepo] = explode('/', $project->github_repo);

        $hook = $github->api('repo')->hooks()->create($githubUser, $githubRepo, [
            'name' => 'web',
            'config' => [
                'url' => action('WebhookController@githubPullRequest'),
                'content_type' => 'json',
                'secret' => $project->webhook_secret,
                'insecure_ssl' => 0,
            ],
            'events' => ['pull_request'],
            'active' => true,
        ]);

        return redirect()->action('HomeController@index');
    }
}
