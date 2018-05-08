<?php

namespace Tests\Feature;

use App\Branch;
use App\Forge;
use App\Jobs\DeploySite;
use App\Jobs\RemoveInitialDeployment;
use App\Jobs\RemoveSite;
use App\Jobs\SetupSite;
use App\Jobs\SetupSql;
use App\Project;
use App\User;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Themsaid\Forge\Resources\Site;

class JobsTest extends TestCase
{
    use RefreshDatabase;

    public function testDeploySiteSuccessful()
    {
        $user = factory(User::class)->create();
        $project = factory(Project::class)->make();
        $user->projects()->save($project);

        $branch = \Mockery::mock(Branch::class)->makePartial();
        $branch->shouldReceive('githubStatus')->once()->with('pending', \Mockery::any());
        $branch->shouldReceive('githubStatus')->once()->with('success', \Mockery::any(), \Mockery::any());
        $branch->shouldReceive('githubComment')->once();
        $branch->project = $project;

        $forgeSite = \Mockery::mock(Site::class);
        $forgeSite->deploymentStatus = 'not null';
        $forgeSite->id = 1337;

        $completedSite = \Mockery::mock(Site::class);
        $completedSite->deploymentStatus = null;
        $completedSite->id = 1337;

        $forgeMock = \Mockery::mock(Forge::class);
        $forgeMock->shouldReceive('setApiKey')->once()->andReturnSelf();
        $forgeMock->shouldReceive('deploySite')->once();
        $forgeMock->shouldReceive('site')->once()->andReturn($forgeSite);
        $forgeMock->shouldReceive('site')->once()->andReturn($completedSite);
        $forgeMock->shouldReceive('siteDeploymentLog')->once()->andReturn("successful-deployment-1337");
        $this->app->instance(Forge::class, $forgeMock);

        DeploySite::dispatchNow($branch);
    }

    public function testDeploySiteFailedLogMissing()
    {
        $user = factory(User::class)->create();
        $project = factory(Project::class)->make();
        $user->projects()->save($project);

        $branch = \Mockery::mock(Branch::class)->makePartial();
        $branch->shouldReceive('githubStatus')->once()->with('pending', \Mockery::any());
        $branch->shouldReceive('githubStatus')->once()->with('failure', \Mockery::any());
        $branch->shouldReceive('githubComment')->once();
        $branch->project = $project;

        $forgeSite = \Mockery::mock(Site::class);
        $forgeSite->deploymentStatus = null;
        $forgeSite->id = 1337;

        $forgeMock = \Mockery::mock(Forge::class);
        $forgeMock->shouldReceive('setApiKey')->once()->andReturnSelf();
        $forgeMock->shouldReceive('deploySite')->once();
        $forgeMock->shouldReceive('site')->once()->andReturn($forgeSite);
        $forgeMock->shouldReceive('siteDeploymentLog')->once()->andThrow(new \Themsaid\Forge\Exceptions\NotFoundException());
        $this->app->instance(Forge::class, $forgeMock);

        DeploySite::dispatchNow($branch);
    }

    public function testDeploySiteFailedDeploymentErrors()
    {
        $user = factory(User::class)->create();
        $project = factory(Project::class)->make();
        $user->projects()->save($project);

        $branch = \Mockery::mock(Branch::class)->makePartial();
        $branch->shouldReceive('githubStatus')->once()->with('pending', \Mockery::any());
        $branch->shouldReceive('githubStatus')->once()->with('failure', \Mockery::any());
        $branch->shouldReceive('githubComment')->once();
        $branch->project = $project;

        $forgeSite = \Mockery::mock(Site::class);
        $forgeSite->deploymentStatus = null;
        $forgeSite->id = 1337;

        $forgeMock = \Mockery::mock(Forge::class);
        $forgeMock->shouldReceive('setApiKey')->once()->andReturnSelf();
        $forgeMock->shouldReceive('deploySite')->once();
        $forgeMock->shouldReceive('site')->once()->andReturn($forgeSite);
        $forgeMock->shouldReceive('siteDeploymentLog')->once()->andReturn('errors');
        $this->app->instance(Forge::class, $forgeMock);

        DeploySite::dispatchNow($branch);
    }

    public function testRemoveInitialDeployment()
    {
        $user = factory(User::class)->create();
        $project = factory(Project::class)->make();
        $branch = factory(Branch::class)->make();
        $user->projects()->save($project);
        $project->branches()->save($branch);

        $forgeMock = \Mockery::mock(Forge::class);
        $forgeMock->shouldReceive('setApiKey')->once()->andReturnSelf();
        $forgeMock->shouldReceive('siteDeploymentScript')->once()->andReturn('');
        $forgeMock->shouldReceive('updateSiteDeploymentScript')->once();
        $this->app->instance(Forge::class, $forgeMock);

        RemoveInitialDeployment::dispatchNow($branch);
    }

    public function testRemoveSite()
    {
        $user = factory(User::class)->create();
        $project = factory(Project::class)->make();
        $branch = factory(Branch::class)->make();
        $user->projects()->save($project);
        $project->branches()->save($branch);

        $forgeMock = \Mockery::mock(Forge::class);
        $forgeMock->shouldReceive('setApiKey')->once()->andReturnSelf();
        $forgeMock->shouldReceive('deleteMysqlUser')->once();
        $forgeMock->shouldReceive('deleteMysqlDatabase')->once();
        $forgeMock->shouldReceive('deleteSite')->once();
        $this->app->instance(Forge::class, $forgeMock);

        RemoveSite::dispatchNow($branch);
    }

    public function testSetupSite()
    {
        Bus::fake();

        $user = factory(User::class)->create();

        $branch = \Mockery::mock(Branch::class)->makePartial();
        $branch->shouldReceive('githubStatus')->once()->with('pending', \Mockery::any());
        $branch->shouldReceive('save')->once();

        $project = \Mockery::mock(Project::class)->makePartial();
        $project->shouldReceive('branches->firstOrNew')->once()->andReturn($branch);
        $project->user = $user;
        $branch->project = $project;

        $pullRequest = [
            'number' => 1337,
            'head' => [
                'sha' => 'a9993e364706816aba3e25717850c26c9cd0d89d',
                'ref' => 'branchname',
                'repo' => [
                    'full_name' => 'test/repo',
                ],
            ],
        ];

        $forgeSite = \Mockery::mock(Site::class);
        $forgeSite->deploymentStatus = 'not null';
        $forgeSite->id = 1337;
        $forgeSite->status = null;
        $forgeSite->repositoryStatus = null;

        $forgeSiteInstalled = \Mockery::mock(Site::class);;
        $forgeSiteInstalled->shouldReceive('installGitRepository')->once();
        $forgeSiteInstalled->shouldReceive('getDeploymentScript')->once()->andReturn('');
        $forgeSiteInstalled->shouldReceive('updateDeploymentScript')->once();
        $forgeSiteInstalled->status = 'installed';

        $forgeSiteInstalledRepository = \Mockery::mock(Site::class);;
        $forgeSiteInstalledRepository->repositoryStatus = 'installed';

        $forgeMock = \Mockery::mock(Forge::class);
        $forgeMock->shouldReceive('setApiKey')->once()->andReturnSelf();
        $forgeMock->shouldReceive('createSite')->once()->andReturn($forgeSite);
        $forgeMock->shouldReceive('site')->once()->andReturn($forgeSiteInstalled);
        $forgeMock->shouldReceive('site')->once()->andReturn($forgeSiteInstalledRepository);

        $job = new SetupSite($project, $pullRequest);
        $job->handle($forgeMock);

        Bus::assertDispatched(SetupSql::class);
    }

    public function testSetupSql()
    {
        $user = factory(User::class)->create();
        $project = factory(Project::class)->make();
        $branch = factory(Branch::class)->make();
        $user->projects()->save($project);
        $project->branches()->save($branch);

        $mock = new \Mockery\Mock();
        $mock->id = 1337;

        $forgeMock = \Mockery::mock(Forge::class);
        $forgeMock->shouldReceive('setApiKey')->once()->andReturnSelf();
        $forgeMock->shouldReceive('createMysqlDatabase')->once()->andReturn($mock);
        $forgeMock->shouldReceive('createMysqlUser')->once()->andReturn($mock);
        $forgeMock->shouldReceive('siteEnvironmentFile')->once()->andReturn('composer require');
        $forgeMock->shouldReceive('updateSiteEnvironmentFile')->once();
        $this->app->instance(Forge::class, $forgeMock);

        SetupSql::dispatchNow($branch);
    }
}
