<?php

namespace Tests\Feature;

use App\Branch;
use App\Jobs\DeploySite;
use App\Jobs\RemoveInitialDeployment;
use App\Jobs\RemoveSite;
use App\Jobs\SetupSite;
use App\Jobs\SetupSql;
use App\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function pr()
    {
        return [
            'pull_request' => [

            ],
            'repository' => [
                'full_name' => 'example/repository',
            ],
            'action' => 'none',
        ];
    }

    public function headers($content = '', $secret = '')
    {
        $hash = hash_hmac('sha1', $content, $secret);

        return [
            'X-Hub-Signature' => 'sha1='.$hash,
        ];
    }

    public function testNoPullRequest()
    {
        $pr = $this->pr();
        unset($pr['pull_request']);

        $response = $this->post('/api/webhooks/github/pullrequest', $pr, $this->headers());

        $response->assertRedirect();
    }

    public function testNoRepository()
    {
        $pr = $this->pr();

        unset($pr['repository']);

        $response = $this->post('/api/webhooks/github/pullrequest', $pr, $this->headers());

        $response->assertRedirect();
    }

    public function testNoSignature()
    {
        $response = $this->post('/api/webhooks/github/pullrequest', $this->pr(), []);

        $response->assertRedirect();
    }

    public function testSignatureVerification()
    {
        $project = Project::factory()->create(['user_id' => 1]);
        $branch = Branch::factory()->make();
        $project->branches()->save($branch);

        $pr = $this->pr();
        $pr['repository']['full_name'] = $project->github_repo;
        $pr['pull_request']['number'] = $branch->issue_number;
        $pr['pull_request']['head'] = [
            'sha' => '3161cdc7bf197e4c3a35b9cbe358c79910f27e90',
            'ref' => 'cool-branch',
            'repo' => [
                'full_name' => $project->github_repo,
            ],
        ];

        $headers = $this->headers(json_encode($pr), $project->webhook_secret);

        $response = $this->json('POST', '/api/webhooks/github/pullrequest', $pr, $headers);
        $response->assertSuccessful();

        $headers = $this->headers(json_encode($pr), 'not a working secret');
        $response = $this->json('POST', '/api/webhooks/github/pullrequest', $pr, $headers);
        $response->assertForbidden();
    }

    public function testNoProjectFound()
    {
        $project = Project::factory()->create(['user_id' => 1]);

        $pr = $this->pr();
        $pr['repository']['full_name'] = 'doesnotexist';
        $pr['pull_request']['number'] = 1;
        $pr['pull_request']['head'] = [
            'sha' => '3161cdc7bf197e4c3a35b9cbe358c79910f27e90',
            'ref' => 'cool-branch',
            'repo' => [
                'full_name' => $project->github_repo,
            ],
        ];

        $headers = $this->headers(json_encode($pr), $project->webhook_secret);

        $response = $this->json('POST', '/api/webhooks/github/pullrequest', $pr, $headers);

        $response->assertForbidden();
    }

    public function testOpenedOrReopened()
    {
        Bus::fake();

        $project = Project::factory()->create(['user_id' => 1]);
        $branch = Branch::factory()->make();
        $project->branches()->save($branch);

        $pr = $this->pr();
        $pr['repository']['full_name'] = $project->github_repo;
        $pr['pull_request']['number'] = $branch->issue_number;
        $pr['pull_request']['head'] = [
            'sha' => '3161cdc7bf197e4c3a35b9cbe358c79910f27e90',
            'ref' => 'cool-branch',
            'repo' => [
                'full_name' => $project->github_repo,
            ],
        ];

        $pr['action'] = 'opened';
        $headers = $this->headers(json_encode($pr), $project->webhook_secret);
        $response = $this->json('POST', '/api/webhooks/github/pullrequest', $pr, $headers);
        $response->assertSuccessful();
        Bus::assertDispatched(SetupSite::class);

        $pr['action'] = 'reopened';
        $headers = $this->headers(json_encode($pr), $project->webhook_secret);
        $response = $this->json('POST', '/api/webhooks/github/pullrequest', $pr, $headers);
        $response->assertSuccessful();
        Bus::assertDispatched(SetupSite::class);
    }

    public function testClosed()
    {
        Bus::fake();

        $project = Project::factory()->create(['user_id' => 1]);
        $branch = Branch::factory()->make();
        $project->branches()->save($branch);

        $pr = $this->pr();
        $pr['repository']['full_name'] = $project->github_repo;
        $pr['pull_request']['number'] = $branch->issue_number;
        $pr['pull_request']['head'] = [
            'sha' => '3161cdc7bf197e4c3a35b9cbe358c79910f27e90',
            'ref' => 'cool-branch',
            'repo' => [
                'full_name' => $project->github_repo,
            ],
        ];

        $pr['action'] = 'closed';
        $headers = $this->headers(json_encode($pr), $project->webhook_secret);
        $response = $this->json('POST', '/api/webhooks/github/pullrequest', $pr, $headers);
        $response->assertSuccessful();
        Bus::assertDispatched(RemoveSite::class);
    }

    public function testSynchronize()
    {
        Bus::fake();

        $project = Project::factory()->create(['user_id' => 1]);
        $branch = Branch::factory()->make();
        $project->branches()->save($branch);

        $pr = $this->pr();
        $pr['repository']['full_name'] = $project->github_repo;
        $pr['pull_request']['number'] = $branch->issue_number;
        $pr['pull_request']['head'] = [
            'sha' => '3161cdc7bf197e4c3a35b9cbe358c79910f27e90',
            'ref' => 'cool-branch',
            'repo' => [
                'full_name' => $project->github_repo,
            ],
        ];

        $pr['action'] = 'synchronize';
        $headers = $this->headers(json_encode($pr), $project->webhook_secret);
        $response = $this->json('POST', '/api/webhooks/github/pullrequest', $pr, $headers);
        $response->assertSuccessful();
        Bus::assertDispatched(DeploySite::class);
    }

    public function testOther()
    {
        Bus::fake();

        $project = Project::factory()->create(['user_id' => 1]);
        $branch = Branch::factory()->make();
        $project->branches()->save($branch);

        $pr = $this->pr();
        $pr['repository']['full_name'] = $project->github_repo;
        $pr['pull_request']['number'] = $branch->issue_number;
        $pr['pull_request']['head'] = [
            'sha' => '3161cdc7bf197e4c3a35b9cbe358c79910f27e90',
            'ref' => 'cool-branch',
            'repo' => [
                'full_name' => $project->github_repo,
            ],
        ];
        // $pr['head']['sha'], $pr['head']['ref'], $pr['head']['repo']['full_name']

        $pr['action'] = 'notarealaction';
        $headers = $this->headers(json_encode($pr), $project->webhook_secret);
        $response = $this->json('POST', '/api/webhooks/github/pullrequest', $pr, $headers);
        $response->assertSuccessful();
        Bus::assertNotDispatched(SetupSite::class);
        Bus::assertNotDispatched(RemoveSite::class);
        Bus::assertNotDispatched(DeploySite::class);
        Bus::assertNotDispatched(SetupSql::class);
        Bus::assertNotDispatched(RemoveInitialDeployment::class);
    }
}
