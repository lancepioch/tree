<?php

namespace App\Jobs;

use App\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Themsaid\Forge\Forge;

class InstallRepository implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $branch;
    private $pullRequest;

    /**
     * Create a new job instance.
     *
     * @param Branch $branch
     * @param array $pullRequest
     */
    public function __construct(Branch $branch, array $pullRequest)
    {
        $this->branch = $branch;
        $this->pullRequest = $pullRequest;
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
        $pullRequest = $this->pullRequest;

        $forge->setApiKey($project->user->forge_token, null);
        $site = $forge->site($project->forge_server_id, $branch->forge_site_id);

        // Repository
        $site->installGitRepository([
            'provider'   => 'github',
            'repository' => $pullRequest['head']['repo']['full_name'],
            'branch'     => $pullRequest['head']['ref'],
        ]);

        $deploymentScript = $site->getDeploymentScript();
        $deploymentScript .= "\n\n# Begin " . config('app.name') . " Configuration\n";
        $deploymentScript .= $project->forge_deployment ?? '# No Custom Deployment';
        $deploymentScript .= "\n# Begin Initial Deployment:\n" . ($project->forge_deployment_initial ?? '') . ' # End Initial Deployment';
        $deploymentScript .= "\n\necho 'successful-deployment-{$site->id}'";
        $site->updateDeploymentScript($deploymentScript);
    }
}
