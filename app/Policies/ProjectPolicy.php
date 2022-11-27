<?php

namespace App\Policies;

use App\Project;
use App\User;
use Github\Client;
use Github\ResultPager;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Request;

class ProjectPolicy
{
    use HandlesAuthorization;

    private $github;

    private $request;

    public function __construct(Client $github, Request $request)
    {
        $this->github = $github;
        $this->request = $request;
    }

    private function administrateRepository(User $user, $repository)
    {
        $github = $this->github;

        try {
            $github->authenticate($user->github_token, null, Client::AUTH_ACCESS_TOKEN);

            $paginator = new ResultPager($github);
            $repositories = $paginator->fetchAll($github->api('me'), 'repositories', ['all']);
        } catch (\Exception $exception) {
            return false;
        }

        $repository = collect($repositories)
            ->where('permissions.admin', true)
            ->where('full_name', $repository);

        return $repository->isNotEmpty();
    }

    /**
     * Determine whether the user can view the project.
     *
     * @param  \App\User  $user
     * @param  \App\Project  $project
     * @return mixed
     */
    public function view(User $user, Project $project)
    {
        return $this->administrateRepository($user, $project->github_repo);
    }

    /**
     * Determine whether the user can create projects.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        $repository = $this->request->get('github_repo');

        return $this->administrateRepository($user, $repository);
    }

    /**
     * Determine whether the user can update the project.
     *
     * @param  \App\User  $user
     * @param  \App\Project  $project
     * @return mixed
     */
    public function update(User $user, Project $project)
    {
        return $this->administrateRepository($user, $project->github_repo);
    }

    /**
     * Determine whether the user can delete the project.
     *
     * @param  \App\User  $user
     * @param  \App\Project  $project
     * @return mixed
     */
    public function delete(User $user, Project $project)
    {
        return $this->administrateRepository($user, $project->github_repo);
    }
}
