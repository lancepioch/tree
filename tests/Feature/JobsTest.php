<?php

namespace Tests\Feature;

use App\Branch;
use App\Jobs\CheckSiteDeployment;
use App\Jobs\DeploySite;
use App\Jobs\InstallRepository;
use App\Jobs\RemoveInitialDeployment;
use App\Jobs\RemoveSite;
use App\Jobs\SetupSite;
use App\Jobs\SetupSql;
use App\Jobs\WaitForRepositoryInstallation;
use App\Jobs\WaitForSiteDeployment;
use App\Jobs\WaitForSiteInstallation;
use App\Project;
use App\User;
use Github\Api\AbstractApi;
use Github\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Forge\Exceptions\NotFoundException;
use Laravel\Forge\Forge;
use Laravel\Forge\Resources\Site;
use Tests\TestCase;

class JobsTest extends TestCase
{
    use RefreshDatabase;

    private function mockGithubStatus()
    {
        $aa = \Mockery::mock(AbstractApi::class);
        $aa->shouldReceive('statuses->create')->andReturn([]);
        $aa->shouldReceive('comments->create')->andReturn([]);

        $client = \Mockery::mock(Client::class);
        $client->shouldReceive('authenticate');
        $client->shouldReceive('repo')->andReturn($aa);

        $this->app->instance(Client::class, $client);
    }

    public function testDeploySiteSuccessful()
    {
        $this->mockGithubStatus();

        $user = User::factory()->create();
        $project = Project::factory()->make();
        $user->projects()->save($project);
        $branch = Branch::factory()->create(['project_id' => $project->id]);

        $forgeMock = $this->getForgeMock(DeploySite::class);
        $forgeMock->shouldReceive('deploySite')->once();

        DeploySite::dispatchSync($branch);
    }

    public function testCheckSiteDeployment()
    {
        $this->mockGithubStatus();

        $user = User::factory()->create();
        $project = Project::factory()->make();
        $user->projects()->save($project);
        $branch = Branch::factory()->create(['project_id' => $project->id]);

        $forgeMock = $this->getForgeMock(CheckSiteDeployment::class);
        $forgeMock->shouldReceive('siteDeploymentLog')->once()->andReturn('successful-deployment-1337');

        CheckSiteDeployment::dispatchSync($branch);
    }

    public function testDeploySiteFailedLogMissing()
    {
        $this->mockGithubStatus();

        $user = User::factory()->create();
        $project = Project::factory()->make();
        $user->projects()->save($project);
        $branch = Branch::factory()->create(['project_id' => $project->id]);

        $forgeMock = $this->getForgeMock(CheckSiteDeployment::class);
        $forgeMock->shouldReceive('siteDeploymentLog')->once()->andThrow(new NotFoundException());

        CheckSiteDeployment::dispatchSync($branch);
    }

    public function testDeploySiteFailedDeploymentErrors()
    {
        $this->mockGithubStatus();

        $user = User::factory()->create();
        $project = Project::factory()->make();
        $user->projects()->save($project);
        $branch = Branch::factory()->create(['project_id' => $project->id]);

        $forgeMock = $this->getForgeMock(CheckSiteDeployment::class);
        $forgeMock->shouldReceive('siteDeploymentLog')->once()->andReturn('errors');

        CheckSiteDeployment::dispatchSync($branch);
    }

    public function testRemoveInitialDeployment()
    {
        $user = User::factory()->create();
        $project = Project::factory()->make();
        $branch = Branch::factory()->make();
        $user->projects()->save($project);
        $project->branches()->save($branch);

        $forgeMock = $this->getForgeMock(RemoveInitialDeployment::class);
        $forgeMock->shouldReceive('siteDeploymentScript')->once()->andReturn('');
        $forgeMock->shouldReceive('updateSiteDeploymentScript')->once();

        RemoveInitialDeployment::dispatchSync($branch);
    }

    public function testRemoveSite()
    {
        $this->mockGithubStatus();

        $user = User::factory()->create();
        $project = Project::factory()->make();
        $user->projects()->save($project);
        $branch = Branch::factory()->create([
            'project_id' => $project->id,
            'forge_mysql_user_id' => 'not null',
            'forge_mysql_database_id' => 'not null',
        ]);

        $forgeMock = $this->getForgeMock(RemoveSite::class);
        $forgeMock->shouldReceive('deleteDatabaseUser')->once();
        $forgeMock->shouldReceive('deleteDatabase')->once();
        $forgeMock->shouldReceive('deleteSite')->once();

        RemoveSite::dispatchSync($branch);
    }

    public function testSetupSite()
    {
        Bus::fake();

        $user = User::factory()->create();

        $branch = \Mockery::mock(Branch::class)->makePartial();
        $branch->shouldReceive('githubStatus')->once()->with('pending', \Mockery::any());
        $branch->shouldReceive('save')->once();

        $project = \Mockery::mock(Project::class)->makePartial();
        $project->user = $user;
        $branch->project = $project;

        $forgeSite = \Mockery::mock(Site::class);
        $forgeSite->deploymentStatus = 'not null';
        $forgeSite->id = 1337;
        $forgeSite->status = null;
        $forgeSite->repositoryStatus = null;

        $forgeSiteInstalledRepository = \Mockery::mock(Site::class);
        $forgeSiteInstalledRepository->repositoryStatus = 'installed';

        $forgeMock = $this->getForgeMock(WaitForSiteInstallation::class);
        $forgeMock->shouldReceive('createSite')->once()->andReturn($forgeSite);

        $job = new SetupSite($branch);
        $job->handle($forgeMock);

        Bus::assertDispatched(WaitForSiteInstallation::class);
    }

    public function testInstallRepository()
    {
        $user = User::factory()->create();
        $project = Project::factory()->make();
        $branch = Branch::factory()->make();
        $user->projects()->save($project);
        $project->branches()->save($branch);

        $forgeMock = $this->getForgeMock(InstallRepository::class);
        $forgeMock->shouldReceive('installGitRepositoryOnSite')->once();
        $forgeMock->shouldReceive('siteDeploymentScript')->once();
        $forgeMock->shouldReceive('updateSiteDeploymentScript')->once();

        InstallRepository::dispatchSync($branch);
    }

    public function testSetupSql()
    {
        $user = User::factory()->create();
        $project = Project::factory()->make();
        $branch = Branch::factory()->make();
        $user->projects()->save($project);
        $project->branches()->save($branch);

        $mock = new \Mockery\Mock();
        $mock->id = 1337;

        $forgeMock = $this->getForgeMock(SetupSql::class);
        $forgeMock->shouldReceive('createDatabase')->once()->andReturn($mock);
        $forgeMock->shouldReceive('createDatabaseUser')->once()->andReturn($mock);
        $forgeMock->shouldReceive('siteEnvironmentFile')->once()->andReturn('composer require');
        $forgeMock->shouldReceive('updateSiteEnvironmentFile')->once();

        SetupSql::dispatchSync($branch);
    }

    public function testWaitForSiteInstallation()
    {
        $user = User::factory()->create();
        $project = Project::factory()->make();
        $branch = Branch::factory()->make();
        $user->projects()->save($project);
        $project->branches()->save($branch);

        $forgeSite = \Mockery::mock(Site::class);
        $forgeSite->status = 'not installed';

        $forgeMock = $this->getForgeMock(WaitForSiteInstallation::class);
        $forgeMock->shouldReceive('site')->once()->andReturn($forgeSite);

        WaitForSiteInstallation::dispatchSync($branch);
    }

    public function testWaitForSiteDeployment()
    {
        $user = User::factory()->create();
        $project = Project::factory()->make();
        $branch = Branch::factory()->make();
        $user->projects()->save($project);
        $project->branches()->save($branch);

        $forgeSite = \Mockery::mock(Site::class);
        $forgeSite->deploymentStatus = 'not null';

        $forgeMock = $this->getForgeMock(WaitForSiteDeployment::class);
        $forgeMock->shouldReceive('site')->once()->andReturn($forgeSite);

        WaitForSiteDeployment::dispatchSync($branch);
    }

    public function testWaitRepositoryInstallation()
    {
        $user = User::factory()->create();
        $project = Project::factory()->make();
        $branch = Branch::factory()->make();
        $user->projects()->save($project);
        $project->branches()->save($branch);

        $forgeSite = \Mockery::mock(Site::class);
        $forgeSite->repositoryStatus = 'not installed';

        $forgeMock = $this->getForgeMock(WaitForRepositoryInstallation::class);
        $forgeMock->shouldReceive('site')->once()->andReturn($forgeSite);

        WaitForRepositoryInstallation::dispatchSync($branch);
    }

    private function getForgeMock(string $jobClass)
    {
        $forgeMock = \Mockery::mock(Forge::class);
        $reflectionClass = new \ReflectionClass($jobClass);
        $parameters = $reflectionClass->getMethod('handle')->getParameters();

        foreach ($parameters as $parameter) {
            if ($parameter->getType()->getName() == Forge::class) {
                $forgeMock->shouldReceive('setApiKey')->once()->andReturnSelf();
            }
        }

        $this->app->instance(Forge::class, $forgeMock);

        return $forgeMock;
    }

    private function getPullRequest()
    {
        return [
            'number' => 1337,
            'head' => [
                'sha' => 'a9993e364706816aba3e25717850c26c9cd0d89d',
                'ref' => 'branchname',
                'repo' => [
                    'full_name' => 'test/repo',
                ],
            ],
        ];
    }
}
