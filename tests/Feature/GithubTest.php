<?php

namespace Tests\Feature;

use App\Branch;
use App\Project;
use App\User;
use Github\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class GithubTest extends TestCase
{
    use RefreshDatabase;

    public function testGithubStatus()
    {
        $user = User::factory()->create();
        $user = \Mockery::mock($user)->makePartial();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $branch = Branch::factory()->create(['project_id' => $project->id]);
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
        $user = User::factory()->create();
        $user = \Mockery::mock($user)->makePartial();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $branch = Branch::factory()->create(['project_id' => $project->id]);
        $branch->project = $project;
        $project->user = $user;

        $github = \Mockery::mock(Client::class);
        $github->shouldReceive('authenticate')->andReturn();
        $github->shouldReceive('api->comments->create');
        $user->shouldReceive('githubClient')->once()->andReturn($github);

        $branch->githubComment('comment body');
    }

    public function testGithubLogin()
    {
        $social = new \Laravel\Socialite\Two\User();
        $social->id = 1;
        $social->name = 'name';
        $social->email = 'email@example.com';
        $social->token = 'token';

        Socialite::shouldReceive('driver->user')->once()->andReturn($social);
        $request = $this->get('/login/github/callback');
        $request->assertRedirect();

        $user = User::query()->where('github_id', 1)->first();
        $this->assertNotNull($user);
        $this->assertEquals($user->name, 'name');
        $this->assertEquals($user->email, 'email@example.com');
        $this->assertEquals($user->github_token, 'token');
    }
}
