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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;
use Laravel\Forge\Forge;
use Themsaid\Forge\Resources\Site;

class JobsTest extends TestCase
{
    use RefreshDatabase;

    public function testDeploySiteSuccessful()
    {
        $user = User::factory()->create();
        $project = Project::factory()->make();
        $user->projects()->save($project);

        $branch = \Mockery::mock(Branch::class)->makePartial();
        $branch->shouldReceive('githubStatus')->once()->with('pending', \Mockery::any());
        $branch->project = $project;

        $forgeMock = $this->getForgeMock(DeploySite::class);
        $forgeMock->shouldReceive('deploySite')->once();

        DeploySite::dispatchNow($branch);
    }

    public function testCheckSiteDeployment()
    {
        $user = User::factory()->create();
        $project = Project::factory()->make();
        $user->projects()->save($project);

        $branch = \Mockery::mock(Branch::class)->makePartial();
        $branch->shouldReceive('githubStatus')->once();
        $branch->shouldReceive('githubComment')->once();
        $branch->project = $project;

        $forgeMock = $this->getForgeMock(CheckSiteDeployment::class);
        $forgeMock->shouldReceive('siteDeploymentLog')->once()->andReturn('successful-deployment-1337');

        CheckSiteDeployment::dispatchNow($branch);
    }

    public function testDeploySiteFailedLogMissing()
    {
        $user = User::factory()->create();
        $project = Project::factory()->make();
        $user->projects()->save($project);

        $branch = \Mockery::mock(Branch::class)->makePartial();
        $branch->shouldReceive('githubStatus')->once()->with('failure', \Mockery::any());
        $branch->shouldReceive('githubComment')->once();
        $branch->project = $project;

        $forgeMock = $this->getForgeMock(CheckSiteDeployment::class);
        $forgeMock->shouldReceive('siteDeploymentLog')->once()->andThrow(new Laravel\Forge\Exceptions\NotFoundException());

        CheckSiteDeployment::dispatchNow($branch);
    }

    public function testDeploySiteFailedDeploymentErrors()
    {
        $user = User::factory()->create();
        $project = Project::factory()->make();
        $user->projects()->save($project);

        $branch = \Mockery::mock(Branch::class)->makePartial();
        $branch->shouldReceive('githubStatus')->once()->with('failure', \Mockery::any());
        $branch->shouldReceive('githubComment')->once();
        $branch->project = $project;

        $forgeMock = $this->getForgeMock(CheckSiteDeployment::class);
        $forgeMock->shouldReceive('siteDeploymentLog')->once()->andReturn('errors');

        CheckSiteDeployment::dispatchNow($branch);
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

        RemoveInitialDeployment::dispatchNow($branch);
    }

    public function testRemoveSite()
    {
        $user = User::factory()->create();
        $project = Project::factory()->make();
        $user->projects()->save($project);

        $branch = \Mockery::mock(Branch::class)->makePartial();
        $branch->shouldReceive('githubStatus')->once()->with('success', \Mockery::any());
        $branch->project = $project;
        $branch->forge_mysql_user_id = 'not null';
        $branch->forge_mysql_database_id = 'not null';

        $forgeMock = $this->getForgeMock(RemoveSite::class);
        $forgeMock->shouldReceive('deleteMysqlUser')->once();
        $forgeMock->shouldReceive('deleteMysqlDatabase')->once();
        $forgeMock->shouldReceive('deleteSite')->once();

        RemoveSite::dispatchNow($branch);
    }

    public function testSetupSite()
    {
        Bus::fake();

        $user = User::factory()->create();

        $branch = \Mockery::mock(Branch::class)->makePartial();
        $branch->shouldReceive('githubStatus')->once()->with('pending', \Mockery::any());
        $branch->shouldReceive('save')->once();

        $project = \Mockery::mock(Project::class)->makePartial();
        $project->shouldReceive('branches->firstOrNew')->once()->andReturn($branch);
        $project->user = $user;
        $branch->project = $project;

        $pullRequest = $this->getPullRequest();

        $forgeSite = \Mockery::mock(Site::class);
        $forgeSite->deploymentStatus = 'not null';
        $forgeSite->id = 1337;
        $forgeSite->status = null;
        $forgeSite->repositoryStatus = null;

        $forgeSiteInstalledRepository = \Mockery::mock(Site::class);
        $forgeSiteInstalledRepository->repositoryStatus = 'installed';

        $forgeMock = $this->getForgeMock(WaitForSiteInstallation::class);
        $forgeMock->shouldReceive('createSite')->once()->andReturn($forgeSite);

        $job = new SetupSite($project, $pullRequest);
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

        $pullRequest = $this->getPullRequest();

        InstallRepository::dispatchNow($branch, $pullRequest);
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
        $forgeMock->shouldReceive('createMysqlDatabase')->once()->andReturn($mock);
        $forgeMock->shouldReceive('createMysqlUser')->once()->andReturn($mock);
        $forgeMock->shouldReceive('siteEnvironmentFile')->once()->andReturn('composer require');
        $forgeMock->shouldReceive('updateSiteEnvironmentFile')->once();

        SetupSql::dispatchNow($branch);
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

        WaitForSiteInstallation::dispatchNow($branch);
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

        WaitForSiteDeployment::dispatchNow($branch);
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

        WaitForRepositoryInstallation::dispatchNow($branch);
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
