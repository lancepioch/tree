<?php

namespace App\Jobs;

use App\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Themsaid\Forge\Forge;

class DeploySite implements ShouldQueue
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
     * @param Forge $forge
     * @return void
     */
    public function handle(Forge $forge)
    {
        $branch = $this->branch;
        $project = $branch->project;

        $forge->setApiKey($project->user->forge_token, null);

        $branch->githubStatus('pending', 'Deploying your branch.');
        $forge->deploySite($project->forge_server_id, $branch->forge_site_id);
        $site = $forge->site($project->forge_server_id, $branch->forge_site_id);

        while ($site->deploymentStatus !== null) {
            sleep(5);
            $site = $forge->site($project->forge_server_id, $site->id);
        }

        try {
            $deploymentLog = $forge->siteDeploymentLog($project->forge_server_id, $site->id);
            $deploymentSuccess = Str::contains($deploymentLog, "successful-deployment-{$site->id}");
        } catch (\Themsaid\Forge\Exceptions\NotFoundException $exception) {
            $branch->githubStatus('failure', 'Failed to deployed your branch.');
            $branch->githubComment(config('app.name') . ' Build Failure Log:' . "\nDeployment Log doesn't exist.");

            return;
        }

        if (!$deploymentSuccess) {
            $branch->githubStatus('failure', 'Failed to deployed your branch.');
            $branch->githubComment(config('app.name') . ' Build Failure Log:' . "\n\n" . $deploymentLog);

            return;
        }

        $url = str_replace('*', $branch->issue_number, $project->forge_site_url);

        if (is_string($url)) {
            $branch->githubStatus('success', 'Deployed your branch.', 'http://' . $url);
            $branch->githubComment(config('app.name') . ' Build URL: http://' . $url);
        }
    }
}
