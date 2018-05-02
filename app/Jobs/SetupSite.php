<?php

namespace App\Jobs;

use App\Project;
use Github\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
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
     * @param $pullRequest
     */
    public function __construct(Project $project, $pullRequest)
    {
        $this->project = $project;
        $this->pullRequest = $pullRequest;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $project = $this->project;
        $pullRequest = $this->pullRequest;

        $forge = new Forge($project->user->forge_token);
        $github = new Client();
        $github->authenticate($project->user->github_token, null, Client::AUTH_HTTP_PASSWORD);
        [$githubUser, $githubRepo] = explode('/', $project->github_repo);

        $status = $github
            ->api('repo')
            ->statuses()
            ->create($githubUser, $githubRepo, $pullRequest['head']['sha'], [
                'state' => 'pending',
                'description' => 'Setting up your branch to be deployed via ' . config('app.name'),
                'context' => config('app.name'),
            ]);

        // Site
        $url = str_replace('*', $pullRequest['number'], $project->forge_site_url);
        $site = $forge->createSite($project->forge_server_id, [
            'domain' => $url,
            'project_type' => 'php',
            'directory' => '/public',
        ], false);

        $branch = $project->branches()->firstOrNew(['issue_number' => $pullRequest['number']]);
        $branch->forge_site_id  = $site->id;
        $branch->save();

        while ($site->status !== 'installed') {
            sleep(5);
            $site = $forge->site($project->forge_server_id, $site->id);
        }

        // Repository
        $site->installGitRepository([
            'provider' => 'github',
            'repository' => $pullRequest['head']['repo']['full_name'],
            'branch' => $pullRequest['head']['ref'],
        ]);

        $deploymentScript = $site->getDeploymentScript();
        $deploymentScript .= "\n\nif [ -f composer.json ]; then composer install --no-interaction --prefer-dist --optimize-autoloader; fi";
        $deploymentScript .= "\nif [ -f artisan ]; then php artisan key:generate; fi";
        $deploymentScript .= "\n" . $project->forge_deployment ?? '# No Custom Deployment';
        $deploymentScript .= "\n\necho 'successful-deployment-{$site->id}'";
        $site->updateDeploymentScript($deploymentScript);

        while ($site->repositoryStatus !== 'installed') {
            sleep(5);
            $site = $forge->site($project->forge_server_id, $site->id);
        }

        sleep(10);

        SetupSql::withChain([new DeploySite($branch, $pullRequest)])->dispatch($branch);
    }
}
