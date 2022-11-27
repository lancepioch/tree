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

    public function __construct(private readonly Branch $branch, private readonly array $pullRequest)
    {
    }

    public function handle(Forge $forge)
    {
        $branch = $this->branch;
        $project = $branch->project;
        $pullRequest = $this->pullRequest;

        $forge->setApiKey($project->user->forge_token, null);

        $forge->installGitRepositoryOnSite($project->forge_server_id, $branch->forge_site_id, [
            'provider' => 'github',
            'repository' => $pullRequest['head']['repo']['full_name'],
            'branch' => $pullRequest['head']['ref'],
        ]);

        $deploymentScript = $forge->siteDeploymentScript($project->forge_server_id, $branch->forge_site_id);
        $deploymentScript .= "\n\n# Begin ".config('app.name')." Configuration\n";
        $deploymentScript .= $project->forge_deployment ?? '# No Custom Deployment';
        $deploymentScript .= "\n# Begin Initial Deployment:\n".($project->forge_deployment_initial ?? '').' # End Initial Deployment';

        foreach ($project->forge_env_vars ?? [] as $key => $value) {
            $key = preg_quote($key);
            $value = preg_quote($value);
            $deploymentScript .= "\nsed -i -E 's/$key=.+/$key=$value/' .env";
        }

        $deploymentScript .= "\n\necho 'successful-deployment-{$branch->forge_site_id}'";

        $forge->updateSiteDeploymentScript($project->forge_server_id, $branch->forge_site_id, $deploymentScript);
    }
}
