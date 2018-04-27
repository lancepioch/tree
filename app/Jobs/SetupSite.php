<?php

namespace App\Jobs;

use App\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Themsaid\Forge\Forge;

class SetupSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Project $project, $pullRequest)
    {
        $forge = new Forge($project->user->forge_token);

        // Site
        $url = str_replace('*', $pullRequest['number'], $project->forge_site_url);
        $site = $forge->createSite($project->forge_server_id, [
            'domain' => $url,
            'project_type' => 'php',
            'directory' => '/public',
        ], true);

        $branch = Branch::create([
            'issue_number' => $pullRequest['number'],
            'forge_site_id' => $site->id,
        ]);

        // Repository
        $site->installGitRepository([
            'provider' => 'github',
            'repository' => $pullRequest['head']['repo']['full_name'],
            'branch' => $pullRequest['head']['ref'],
        ]);

        // MySQL
        $sqlUsername = 'pull_request_' . $pullRequest['number'];
        $sqlPassword = str_random(20);
        $mysql = $forge->createMysqlDatabase($project->forge_server_id, [
            'name' => $sqlUsername,
            'user' => $sqlUsername,
            'password' => $sqlPassword,
        ], true);

        // Environment
        $environment = $forge->siteEnvironmentFile($project->forge_server_id, $site->id);
        $environment = preg_replace('/^DB_DATABASE=.*$/', 'DB_DATABASE='.$sqlUsername, $environment);
        $environment = preg_replace('/^DB_USERNAME=.*$/', 'DB_USERNAME='.$sqlUsername, $environment);
        $environment = preg_replace('/^DB_PASSWORD=.*$/', 'DB_PASSWORD='.$sqlPassword, $environment);

        if (strlen($environment) > 0) {
            $forge->updateSiteEnvironmentFile($project->forge_server_id, $site->id, $environment);
        }

        // Deployment
        // $deploymentScript = $forge->siteDeploymentScript($project->forge_server_id, $site->id);
        // $forge->updateSiteDeploymentScript($project->forge_server_id, $site->id, $deploymentScript);
        $site->enableQuickDeploy();
        $site->deploySite();
        // $deploymentLog = $forge->siteDeploymentLog($project->forge_server_id, $site->id);


        // $client->api('issue')->comments()->create('username', 'repository', $pullRequest['number'], ['body' => 'Build URL: ' . $url]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
    }
}
