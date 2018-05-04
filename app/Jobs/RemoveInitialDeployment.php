<?php

namespace App\Jobs;

use App\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Themsaid\Forge\Forge;

class RemoveInitialDeployment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $branch;

    /**
     * Create a new job instance.
     *
     * @param Branch $branch
     */
    public function __construct(Branch $branch)
    {
        $this->branch = $branch;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $branch = $this->branch;
        $project = $branch->project;

        $forge = new Forge($project->user->forge_token);
        $deploymentScript = $forge->siteDeploymentScript($project->forge_server_id, $branch->forge_site_id);
        $deploymentScript = preg_replace('\n/^# Begin Initial Deployment:.*# End Initial Deployment$/ms', '', $deploymentScript);
        $forge->updateSiteDeploymentScript($project->forge_server_id, $branch->forge_site_id, $deploymentScript);
    }
}
