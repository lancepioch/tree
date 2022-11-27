<?php

namespace App\Jobs;

use App\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Forge\Forge;

class SetupSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @param  array<string, string|array<string, string>>  $pullRequest */
    public function __construct(private readonly Project $project, private readonly array $pullRequest)
    {
    }

    public function handle(Forge $forge): void
    {
        $project = $this->project;
        $pullRequest = $this->pullRequest;

        $forge = $forge->setApiKey($project->user->forge_token, null);

        /** @var \App\Branch $branch */
        $branch = $project->branches()->firstOrNew(['issue_number' => $pullRequest['number']], [
            'commit_hash' => $pullRequest['head']['sha'],
        ]);

        $branch->githubStatus('pending', 'Setting up your branch to be deployed.');

        // Site
        $url = str_replace('*', $pullRequest['number'], $project->forge_site_url);

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
            new InstallRepository($branch, $pullRequest),
            new WaitForRepositoryInstallation($branch),
            new SetupSql($branch),
            new DeploySite($branch),
            new WaitForSiteDeployment($branch),
            new CheckSiteDeployment($branch),
            new RemoveInitialDeployment($branch),
        ])->dispatch($branch);
    }
}
