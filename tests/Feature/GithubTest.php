<?php

namespace Tests\Feature;

use App\Branch;
use App\Project;
use App\User;
use Github\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GithubTest extends TestCase
{
    use RefreshDatabase;

    public function testGithubStatus()
    {
        $user = factory(User::class)->create();
        $user = \Mockery::mock($user)->makePartial();
        $project = factory(Project::class)->create(['user_id' => $user->id]);
        $branch = factory(Branch::class)->create(['project_id' => $project->id]);
        $branch->project = $project;
        $project->user = $user;

        $github = \Mockery::mock(Client::class);
        $github->shouldReceive('authenticate')->andReturn();
        $github->shouldReceive('api->statuses->create');
        $user->shouldReceive('githubClient')->once()->andReturn($github);

        $branch->githubStatus('success', 'my description', '*.test.example.com');
    }

    public function testGithubComment()
    {
        $user = factory(User::class)->create();
        $user = \Mockery::mock($user)->makePartial();
        $project = factory(Project::class)->create(['user_id' => $user->id]);
        $branch = factory(Branch::class)->create(['project_id' => $project->id]);
        $branch->project = $project;
        $project->user = $user;

        $github = \Mockery::mock(Client::class);
        $github->shouldReceive('authenticate')->andReturn();
        $github->shouldReceive('api->comments->create');
        $user->shouldReceive('githubClient')->once()->andReturn($github);

        $branch->githubComment('comment body');
    }
}
