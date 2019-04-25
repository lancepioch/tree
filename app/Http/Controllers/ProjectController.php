<?php

namespace App\Http\Controllers;

use App\Project;
use Github\Client;
use Github\Api\Repo;
use Illuminate\Http\Request;
use Github\Exception\RuntimeException;

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
        $this->authorizeResource(Project::class);
    }

    public function index()
    {
        return redirect()->action('HomeController@index');
    }

    public function create()
    {
        return redirect()->action('ProjectController@index');
    }

    /**
     * Creates a new project.
     *
     * @param Request $request
     * @param Client $github
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Github\Exception\MissingArgumentException
     */
    public function store(Request $request, Client $github)
    {
        $input = $request->all() + ['webhook_secret' => str_random(20)];

        $project = Project::onlyTrashed()->where('github_repo', $request->get('github_repo'))->first();

        if ($project === null) {
            $project = new Project($input);
        }

        if ($project->trashed()) {
            $project->restore();
        }

        $github->authenticate(auth()->user()->github_token, null, Client::AUTH_HTTP_PASSWORD);
        [$githubUser, $githubRepo] = explode('/', $project->github_repo);

        $repo = new Repo($github);
        $hook = $repo->hooks()->create($githubUser, $githubRepo, [
            'name'   => 'web',
            'config' => [
                'url'          => action('Webhooks\GithubPullRequestController'),
                'content_type' => 'json',
                'secret'       => $project->webhook_secret,
                'insecure_ssl' => 0,
            ],
            'events' => ['pull_request'],
            'active' => true,
        ]);

        $project->webhook_id = $hook['id'];

        auth()->user()->projects()->save($project);

        return redirect()->action('HomeController@index');
    }

    public function show(Project $project)
    {
        return view('project')->with(compact('project'));
    }

    public function edit(Project $project)
    {
        return redirect()->action('ProjectController@show', [$project]);
    }

    public function update(Project $project, Request $request)
    {
        $project->fill($request->except(['forge_server_id', 'github_repo', 'webhook_secret']));
        $project->save();

        return redirect()->action('ProjectController@index', [$project]);
    }

    /**
     * @param Project $project
     * @param Client $github
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(Project $project, Client $github)
    {
        $github->authenticate($project->user->github_token, null, Client::AUTH_HTTP_PASSWORD);
        [$githubUser, $githubRepo] = explode('/', $project->github_repo);

        try {
            $repo = new Repo($github);
            $repo->hooks()->remove($githubUser, $githubRepo, $project->webhook_id);
        } catch (RuntimeException $exception) {
            // Hook has already been removed or we don't have access anymore, either way just trek on ahead
        }

        $project->delete();

        return redirect()->action('ProjectController@index');
    }
}
