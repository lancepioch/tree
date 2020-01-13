<?php

namespace App\Jobs;

use App\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Themsaid\Forge\Forge;

class SetupSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $project;
    private $pullRequest;

    /**
     * Create a new job instance.
     *
     * @param Project $project
     * @param array $pullRequest
     */
    public function __construct(Project $project, array $pullRequest)
    {
        $this->project = $project;
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
        $site = $forge->createSite($project->forge_server_id, [
            'domain'       => $url,
            'project_type' => 'php',
            'directory'    => '/public',
        ], false);

        $branch->forge_site_id = $site->id;
        $branch->save();

        while ($site->status !== 'installed') {
            sleep(5);
            $site = $forge->site($project->forge_server_id, $site->id);
        }

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

        while ($site->repositoryStatus !== 'installed') {
            sleep(5);
            $site = $forge->site($project->forge_server_id, $site->id);
        }

        sleep(10);

        SetupSql::withChain([new DeploySite($branch), new RemoveInitialDeployment($branch)])->dispatch($branch);
    }
}
