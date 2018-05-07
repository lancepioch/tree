<?php

namespace Tests\Feature;

use App\Project;
use App\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function testProjectView()
    {
        $user = factory(User::class)->create();
        $anotherUser = factory(User::class)->create();
        $project = factory(Project::class)->make();
        $user->projects()->save($project);

        Gate::before(function ($policyUser) use ($project) {
            return $policyUser->id === $project->user_id;
        });

        $response = $this->actingAs($user)
            ->get("/projects/{$project->id}");

        $response->assertStatus(200);
        $response->assertSee($project->forge_site_url);
        $response->assertSee($project->forge_deployment);
        $response->assertSee($project->forge_deployment_initial);

        $response = $this->actingAs($anotherUser)
            ->get("/projects/{$project->id}");

        $response->assertStatus(403);
        $response->assertDontSee($project->forge_site_url);
        $response->assertDontSee($project->forge_deployment);
        $response->assertDontSee($project->forge_deployment_initial);

    }

    public function testProjectCreate()
    {
        $this->markTestIncomplete();
    }

    public function testProjectUpdate()
    {
        $this->markTestIncomplete();
    }

    public function testProjectDelete()
    {
        $this->markTestIncomplete();
    }
}
