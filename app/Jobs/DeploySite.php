<?php

namespace App\Jobs;

use App\Branch;
use App\Project;
use Github\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Themsaid\Forge\Forge;

class DeploySite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $branch;
    private $pullRequest;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Branch $branch, $pullRequest)
    {
        $this->project = $branch;
        $this->pullRequest = $pullRequest;
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
        $pullRequest = $this->pullRequest;

        $forge = new Forge($project->user->forge_token);
        $github = new Client();
        $github->authenticate($project->user->github_token, null, Client::AUTH_HTTP_PASSWORD);
        [$githubUser, $githubRepo] = explode('/', $project->github_repo);

        $site = $forge->site($project->forge_server_id, $branch->forge_site_id);

        $status = $github
            ->api('repo')
            ->statuses()
            ->create($githubUser, $githubRepo, $pullRequest['head']['sha'], [
                'state' => 'pending',
                'description' => 'Deploying your branch via ' . config('app.name'),
                'context' => config('app.name'),
            ]);

        // Deployment
        $deploymentScript = $site->getDeploymentScript();
        $deploymentScript .= "\n\nif [ -f composer.json ]; then composer install --no-interaction --prefer-dist --optimize-autoloader; fi";
        $deploymentScript .= "\nif [ -f artisan ]; then php artisan key:generate; fi";
        $site->updateDeploymentScript($deploymentScript);


        while ($site->repositoryStatus === 'installing') {
            sleep(1);
            $site = $forge->site($project->forge_server_id, $site->id);
        }

        $site->deploySite();

        // $deploymentLog = $forge->siteDeploymentLog($project->forge_server_id, $site->id);
        // $forge->obtainLetsEncryptCertificate($project->forge_server_id, $site->id, ['domains' => [$url]]);

        $url = str_replace('*', $pullRequest['number'], $project->forge_site_url);
        echo "<a href=\"http://$url\">http://$url</a>";

        $status = $github
            ->api('repo')
            ->statuses()
            ->create($githubUser, $githubRepo, $pullRequest['head']['sha'], [
                'state' => 'success',
                'description' => 'Deploying your branch via ' . config('app.name'),
                'context' => config('app.name'),
                'target_url' => 'http://' . $url,
            ]);

        $github->api('issue')
            ->comments()
            ->create($githubUser, $githubRepo, $pullRequest['number'], [
                'body' => config('app.name') . ' Build URL: http://' . $url,
            ]);
    }
}
