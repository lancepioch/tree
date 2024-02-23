<?php

namespace App\Jobs;

use App\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Laravel\Forge\Forge;

class CheckSiteDeployment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly Branch $branch)
    {
    }

    public function handle(Forge $forge): void
    {
        $branch = $this->branch;
        $project = $branch->project;
        $forge = $forge->setApiKey($project->user->forge_token, null);

        try {
            $deploymentLog = $forge->siteDeploymentLog($project->forge_server_id, $branch->forge_site_id);
            $deploymentSuccess = Str::contains($deploymentLog, "successful-deployment-{$branch->forge_site_id}");
        } catch (\Laravel\Forge\Exceptions\NotFoundException) {
            $branch->githubStatus('failure', 'Failed to deploy the branch because the deployment log doesn\'t exist.');
            $branch->githubComment(config('app.name').' Build Failure Log:'."\nDeployment Log doesn't exist.");

            return;
        }

        if (! $deploymentSuccess) {
            $branch->githubStatus('failure', 'Failed to deploy your branch because of build issues.');
            $branch->githubComment(config('app.name').' Build Failure Log:'."\n\n".$deploymentLog);

            return;
        }

        $url = str_replace('*', (string) $branch->issue_number, $project->forge_site_url);
        $branch->githubStatus('success', 'Deployed your branch.', 'http://'.$url);
        $branch->githubComment(config('app.name').' Build URL: http://'.$url);
    }
}
