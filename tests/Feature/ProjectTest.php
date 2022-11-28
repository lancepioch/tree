<?php

namespace Tests\Feature;

use App\Project;
use App\User;
use Github\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function testProjectIndex()
    {
        Gate::before(function () {
            return true;
        });

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/projects');
        $response->assertRedirect('/home');
    }

    public function testProjectCreate()
    {
        Gate::before(function () {
            return true;
        });

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/projects/create');
        $response->assertRedirect('/projects');
    }

    public function testProjectEdit()
    {
        Gate::before(function () {
            return true;
        });

        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => 1]);
        $response = $this->actingAs($user)->get("/projects/{$project->id}/edit");
        $response->assertRedirect("/projects/{$project->id}");
    }

    public function testProjectView()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $project = Project::factory()->make();
        $user->projects()->save($project);

        Gate::before(function ($policyUser) use ($project) {
            return $policyUser->id === $project->user_id;
        });

        $response = $this->actingAs($user)
            ->get("/projects/{$project->id}");

        $response->assertSuccessful();
        $response->assertSee($project->forge_site_url);
        $response->assertSee($project->forge_deployment);
        $response->assertSee($project->forge_deployment_initial);

        $response = $this->actingAs($anotherUser)
            ->get("/projects/{$project->id}");

        $response->assertForbidden();
        $response->assertDontSee($project->forge_site_url);
        $response->assertDontSee($project->forge_deployment);
        $response->assertDontSee($project->forge_deployment_initial);
    }

    public function testProjectStore()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        Gate::before(function ($policyUser) use ($user) {
            return $policyUser->id === $user->id;
        });

        $this->app->instance(Client::class, new FakeClient());

        $response = $this->actingAs($user)->post('/projects/', [
            'forge_site_url' => '*.test.com',
            'forge_server_id' => 1,
            'github_repo' => 'test/repo',
            'webhook_secret' => '1234567890',
            'webhook_id' => 1,
            'forge_deployment' => 'composer require',
            'forge_deployment_initial' => 'php artisan key:generate',
        ]);
        $response->assertRedirect('/home');
        $response = $this->actingAs($user)->get('/home');
        $response->assertSee('*.test.com');
        $response->assertSee('test/repo');

        $project = $user->projects()->first();
        $project->delete();
        $this->assertTrue($project->trashed());
        $response = $this->actingAs($user)->post('/projects/', [
            'forge_site_url' => '*.test.com',
            'forge_server_id' => 1,
            'github_repo' => 'test/repo',
            'webhook_secret' => '1234567890',
            'webhook_id' => 1,
            'forge_deployment' => 'composer require',
            'forge_deployment_initial' => 'php artisan key:generate',
        ]);
        $response->assertRedirect('/home');
        $response = $this->actingAs($user)->get('/home');
        $response->assertSee('*.test.com');
        $response->assertSee('test/repo');
        $newProject = $user->projects()->first();
        $this->assertSame($project->id, $newProject->id);
        $this->assertFalse($newProject->trashed());

        $response = $this->actingAs($anotherUser)->post('/projects/', [
            'forge_site_url' => '*.test.com',
            'forge_server_id' => 1,
            'github_repo' => 'test/repo',
            'webhook_secret' => '1234567890',
            'webhook_id' => 1,
            'forge_deployment' => 'composer require',
            'forge_deployment_initial' => 'php artisan key:generate',
        ]);
        $response->assertForbidden();
        $response = $this->actingAs($anotherUser)->get('/home');
        $response->assertDontSee('*.test.com');
        $response->assertDontSee('test/repo');
    }

    public function testProjectUpdate()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $project = Project::factory()->make();
        $user->projects()->save($project);

        Gate::before(function ($policyUser) use ($project) {
            return $policyUser->id === $project->user_id;
        });

        $response = $this->actingAs($anotherUser)->put("/projects/{$project->id}", [
            'forge_site_url' => '*.test.com',
            'forge_server_id' => -1,
            'github_repo' => 'test/repo',
            'webhook_secret' => '1234567890',
            'webhook_id' => -1,
            'forge_deployment' => 'composer require',
            'forge_deployment_initial' => 'php artisan key:generate',
        ]);
        $response->assertForbidden();

        $projectLatest = $project->fresh();
        $this->assertSame($project->forge_site_url, $projectLatest->forge_site_url);
        $this->assertSame($project->forge_server_id, $projectLatest->forge_server_id);
        $this->assertSame($project->github_repo, $projectLatest->github_repo);
        $this->assertSame($project->webhook_secret, $projectLatest->webhook_secret);
        $this->assertSame($project->webhook_id, $projectLatest->webhook_id);
        $this->assertSame($project->forge_deployment, $projectLatest->forge_deployment);
        $this->assertSame($project->forge_deployment_initial, $projectLatest->forge_deployment_initial);

        $response = $this->actingAs($user)->put("/projects/{$project->id}", [
            'forge_site_url' => '*.test.com',
            'forge_server_id' => -1,
            'github_repo' => 'test/repo',
            'webhook_secret' => '1234567890',
            'webhook_id' => 12345,
            'forge_deployment' => 'composer require',
            'forge_deployment_initial' => 'php artisan key:generate',
        ]);
        $project = $project->fresh();

        $this->assertSame('*.test.com', $project->forge_site_url);
        $this->assertNotSame(-1, $project->forge_server_id);
        $this->assertNotSame('test/repo', $project->github_repo);
        $this->assertNotSame('1234567890', $project->webhook_secret);
        $this->assertSame(12345, $project->webhook_id);
        $this->assertSame('composer require', $project->forge_deployment);
        $this->assertSame('php artisan key:generate', $project->forge_deployment_initial);
        $response->assertRedirect("/projects/{$project->id}");
    }

    public function testProjectDelete()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $project = Project::factory()->make();
        $user->projects()->save($project);

        Gate::before(function ($policyUser) use ($user) {
            return $policyUser->id === $user->id;
        });

        $this->app->instance(Client::class, new FakeClient());

        $response = $this->actingAs($anotherUser)->delete("/projects/{$project->id}");
        $response->assertForbidden();
        $this->assertNotNull($project->fresh());

        $response = $this->actingAs($user)->delete("/projects/{$project->id}");
        $response->assertRedirectToRoute('home');
        $this->assertTrue($project->fresh()->trashed());
    }

    public function testProjectPause()
    {
        $user = User::factory()->create();
        $project = Project::factory()->make();
        $project->paused_at = null;
        $user->projects()->save($project);

        Gate::before(function ($policyUser) use ($user) {
            return $policyUser->id === $user->id;
        });

        $response = $this->actingAs($user)->post("/projects/{$project->id}/pause");
        $response->assertRedirect("/projects/{$project->id}");

        $project->refresh();
        $this->assertInstanceOf(\DateTime::class, $project->paused_at);

        $response = $this->actingAs($user)->post("/projects/{$project->id}/pause");
        $response->assertRedirect("/projects/{$project->id}");

        $project->refresh();
        $this->assertNull($project->paused_at);
    }
}

class FakeClient extends Client
{
    public function repo()
    {
        return $this;
    }

    public function hooks()
    {
        return $this;
    }

    public function statuses()
    {
        return $this;
    }

    public function comments()
    {
        return $this;
    }

    public function create()
    {
        return [
            'id' => 12345,
        ];
    }
}
