<?php

namespace App\Http\Controllers;

use App\Project;
use Github\Api\Repo;
use Github\Client;
use Github\Exception\RuntimeException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

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

    public function index(): RedirectResponse
    {
        return redirect()->route('home');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('projects.index');
    }

    /**
     * Creates a new project.
     *
     * @throws \Github\Exception\MissingArgumentException
     */
    public function store(Request $request, Client $github): RedirectResponse
    {
        $request->validate([
            'forge_site_url' => 'regex:/.*\*.+/', // domain must have an asterisk
            'forge_user' => 'nullable|regex:/[a-z][-a-z0-9_]{0,15}/', // optional but valid unix username
            'forge_env_vars' => 'nullable|json',
        ]);

        $input = $request->all() + ['webhook_secret' => Str::random(20)];
        if (isset($input['forge_env_vars'])) {
            $input['forge_env_vars'] = json_decode($input['forge_env_vars'], true);
        }

        $project = Project::onlyTrashed()->where('github_repo', $request->get('github_repo'))->first();

        if ($project === null) {
            $project = new Project();
        }

        $project->fill($input);

        if ($project->trashed()) {
            $project->restore();
        }

        $github->authenticate(auth()->user()->github_token, null, Client::AUTH_ACCESS_TOKEN);
        [$githubUser, $githubRepo] = explode('/', $project->github_repo);

        $hook = $github->repo()->hooks()->create($githubUser, $githubRepo, [
            'name' => 'web',
            'config' => [
                'url' => route('webhooks.github.pullrequest'),
                'content_type' => 'json',
                'secret' => $project->webhook_secret,
                'insecure_ssl' => 0,
            ],
            'events' => ['pull_request'],
            'active' => true,
        ]);

        $project->webhook_id = $hook['id'];

        auth()->user()->projects()->save($project);

        return redirect()->route('home');
    }

    public function show(Project $project): View
    {
        return view('project')->with(compact('project'));
    }

    public function edit(Project $project): RedirectResponse
    {
        return redirect()->route('projects.show', [$project]);
    }

    public function update(Project $project, Request $request): RedirectResponse
    {
        $project->fill($request->except(['forge_server_id', 'github_repo', 'webhook_secret']));
        $project->save();

        return redirect()->route('projects.show', [$project]);
    }

    public function destroy(Project $project, Client $github): RedirectResponse
    {
        $github->authenticate($project->user->github_token, null, Client::AUTH_ACCESS_TOKEN);
        [$githubUser, $githubRepo] = explode('/', $project->github_repo);

        try {
            (new Repo($github))->hooks()->remove($githubUser, $githubRepo, $project->webhook_id);
        } catch (RuntimeException) {
            // Hook has already been removed or we don't have access anymore, either way just trek on ahead
        }

        $project->delete();

        return redirect()->route('home');
    }
}
