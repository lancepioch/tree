<?php

namespace App\Jobs;

use App\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Forge\Forge;

class InstallRepository implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly Branch $branch)
    {
    }

    public function handle(Forge $forge): void
    {
        $branch = $this->branch;
        $project = $branch->project;

        $forge->setApiKey($project->user->forge_token, null);

        $forge->installGitRepositoryOnSite($project->forge_server_id, $branch->forge_site_id, [
            'provider' => 'github',
            'repository' => $branch->head_repo,
            'branch' => $branch->head_ref,
        ]);

        $deploymentScript = "\n\n# Begin ".config('app.name')." Configuration\n";
        $deploymentScript .= $project->forge_deployment ?? '# No Custom Deployment';
        $deploymentScript .= "\n# Begin Initial Deployment:\n".($project->forge_deployment_initial ?? '').' # End Initial Deployment';

        foreach ($project->forge_env_vars ?? [] as $key => $value) {
            $key = addslashes($key);
            $value = addslashes($value);
            $deploymentScript .= "\nsed -i -E 's|$key=.+|$key=$value|' .env";
        }

        $deploymentScript .= "\n\necho 'successful-deployment-$branch->forge_site_id'";

        $forge->updateSiteDeploymentScript($project->forge_server_id, $branch->forge_site_id, $deploymentScript);
    }
}
