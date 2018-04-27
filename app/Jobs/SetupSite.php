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
     * @return void
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
                'description' => 'Deploying your branch via ' . config('app.name'),
                'context' => config('app.name'),
            ]);

        // Site
        $url = str_replace('*', $pullRequest['number'], $project->forge_site_url);
        $site = $forge->createSite($project->forge_server_id, [
            'domain' => $url,
            'project_type' => 'php',
            'directory' => '/public',
        ], false);

        // MySQL
        $sqlUsername = 'pull_request_' . $pullRequest['number'];
        $sqlPassword = str_random(20);
        $mysqlDatabase = $forge->createMysqlDatabase($project->forge_server_id, ['name' => $sqlUsername], false);
        $mysqlUser = $forge->createMysqlUser($project->forge_server_id, [
            'name' => $sqlUsername,
            'password' => $sqlPassword,
            'databases' => [$mysqlDatabase->id],
        ], false);

        /** @var \App\Branch $branch */
        $branch = $project->branches()->create([
            'issue_number' => $pullRequest['number'],
            'forge_site_id' => $site->id,
            'forge_mysql_database_id' => $mysqlDatabase->id,
            'forge_mysql_user_id' => $mysqlUser->id,
        ]);

        while ($site->status !== 'installed') {
            sleep(1);
            $site = $forge->site($project->forge_server_id, $site->id);
        }

        // Repository
        $site->installGitRepository([
            'provider' => 'github',
            'repository' => $pullRequest['head']['repo']['full_name'],
            'branch' => $pullRequest['head']['ref'],
        ]);

        // Environment
        $environment = $forge->siteEnvironmentFile($project->forge_server_id, $site->id);
        $environment = preg_replace('/^DB_DATABASE=.*$/', 'DB_DATABASE='.$sqlUsername, $environment);
        $environment = preg_replace('/^DB_USERNAME=.*$/', 'DB_USERNAME='.$sqlUsername, $environment);
        $environment = preg_replace('/^DB_PASSWORD=.*$/', 'DB_PASSWORD='.$sqlPassword, $environment);

        if (strlen($environment) > 0) {
            $forge->updateSiteEnvironmentFile($project->forge_server_id, $site->id, $environment);
        }

        // Deployment
        $deploymentScript = $site->getDeploymentScript();
        $deploymentScript .= "\n\nif [ -f composer.json ]; then composer install --no-interaction --prefer-dist --optimize-autoloader; fi";
        $deploymentScript .= "\nif [ -f artisan ]; then php artisan key:generate; fi";
        $site->updateDeploymentScript($deploymentScript);

        $site = $forge->site($project->forge_server_id, $site->id);

        while ($site->repositoryStatus === 'installing') {
            sleep(1);
            $site = $forge->site($project->forge_server_id, $site->id);
        }

        $site->deploySite();

        // $deploymentLog = $forge->siteDeploymentLog($project->forge_server_id, $site->id);
        // $forge->obtainLetsEncryptCertificate($project->forge_server_id, $site->id, ['domains' => [$url]]);

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
