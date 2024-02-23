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

    public function __construct(private readonly Client $github, private readonly Request $request)
    {
    }

    private function administrateRepository(User $user, string $repository): bool
    {
        $github = $this->github;

        try {
            $github->authenticate($user->github_token, null, Client::AUTH_ACCESS_TOKEN);

            $paginator = new ResultPager($github);
            $repositories = $paginator->fetchAll($github->api('me'), 'repositories', ['all']);
        } catch (\Exception) {
            return false;
        }

        $repository = collect($repositories)
            ->where('permissions.admin', true)
            ->where('full_name', $repository);

        return $repository->isNotEmpty();
    }

    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        return $this->administrateRepository($user, $project->github_repo);
    }

    /**
     * Determine whether the user can create projects.
     */
    public function create(User $user): bool
    {
        $repository = $this->request->get('github_repo');

        return $this->administrateRepository($user, $repository);
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        return $this->administrateRepository($user, $project->github_repo);
    }

    /**
     * Determine whether the user can delete the project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $this->administrateRepository($user, $project->github_repo);
    }
}
