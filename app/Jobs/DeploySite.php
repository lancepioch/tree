<?php

namespace App\Jobs;

use App\Branch;
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
        $github = new Client();
        $github->authenticate($project->user->github_token, null, Client::AUTH_HTTP_PASSWORD);
        [$githubUser, $githubRepo] = explode('/', $project->github_repo);

        $site = $forge->site($project->forge_server_id, $branch->forge_site_id);

        $github->api('repo')
            ->statuses()
            ->create($githubUser, $githubRepo, $branch->commit_hash, [
                'state' => 'pending',
                'description' => 'Deploying your branch via ' . config('app.name'),
                'context' => config('app.name'),
            ]);

        $site->deploySite();

        while ($site->deploymentStatus !== null) {
            sleep(5);
            $site = $forge->site($project->forge_server_id, $site->id);
        }

        try {
            $deploymentLog = $forge->siteDeploymentLog($project->forge_server_id, $site->id);
            $deploymentSuccess = str_contains($deploymentLog, "successful-deployment-{$site->id}");
        } catch (\Themsaid\Forge\Exceptions\NotFoundException $exception) {
            $this->release(3);
        }

        if (!$deploymentSuccess) {
            $github->api('repo')
                ->statuses()
                ->create($githubUser, $githubRepo, $branch->commit_hash, [
                    'state' => 'failure',
                    'description' => 'Failed to deployed your branch.',
                    'context' => config('app.name'),
                ]);

            $github->api('issue')
                ->comments()
                ->create($githubUser, $githubRepo, $branch->issue_number, [
                    'body' => config('app.name') . ' Build Failure Log:' . "\n\n" . $deploymentLog,
                ]);
            
            return;
        }

        // $forge->obtainLetsEncryptCertificate($project->forge_server_id, $site->id, ['domains' => [$url]]);

        $url = str_replace('*', $branch->issue_number, $project->forge_site_url);

        $github->api('repo')
            ->statuses()
            ->create($githubUser, $githubRepo, $branch->commit_hash, [
                'state' => 'success',
                'description' => 'Deployed your branch via ' . config('app.name'),
                'context' => config('app.name'),
                'target_url' => 'http://' . $url,
            ]);

        $github->api('issue')
            ->comments()
            ->create($githubUser, $githubRepo, $branch->issue_number, [
                'body' => config('app.name') . ' Build URL: http://' . $url,
            ]);
    }
}
