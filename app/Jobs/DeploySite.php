<?php

namespace App\Jobs;

use App\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Forge\Forge;

class DeploySite implements ShouldQueue
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

        $branch->githubStatus('pending', 'Deploying your branch.');
        $forge->deploySite($project->forge_server_id, $branch->forge_site_id);
    }
}
