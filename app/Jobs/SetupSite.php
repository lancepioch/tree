<?php

namespace App\Jobs;

use App\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Forge\Forge;

class SetupSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly Branch $branch)
    {
    }

    public function handle(Forge $forge): void
    {
        $branch = $this->branch;
        $project = $this->branch->project;

        $forge = $forge->setApiKey($project->user->forge_token);

        $branch->githubStatus('pending', 'Setting up your branch to be deployed.');

        $url = str_replace('*', (string) $branch->issue_number, $project->forge_site_url);

        $isolatedUser = [];
        if (! is_null($project->forge_user)) {
            $isolatedUser = [
                'isolated' => true,
                'username' => $project->forge_user,
            ];
        }

        $site = $forge->createSite($project->forge_server_id, [
            'domain' => $url,
            'project_type' => 'php',
            'directory' => '/public',
        ] + $isolatedUser, false);

        $branch->forge_site_id = $site->id;
        $branch->save();

        WaitForSiteInstallation::withChain([
            new InstallRepository($branch),
            new WaitForRepositoryInstallation($branch),
            new SetupSql($branch),
            new DeploySite($branch),
            new WaitForSiteDeployment($branch),
            new CheckSiteDeployment($branch),
            new RemoveInitialDeployment($branch),
        ])->dispatch($branch);
    }
}
