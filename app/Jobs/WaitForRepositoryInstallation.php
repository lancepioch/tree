<?php

namespace App\Jobs;

use App\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Forge\Forge;

class WaitForRepositoryInstallation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $branch;

    /**
     * Create a new job instance.
     *
     * @param  Branch  $branch
     */
    public function __construct(Branch $branch)
    {
        $this->branch = $branch;
    }

    /**
     * Execute the job.
     *
     * @param  Forge  $forge
     * @return void
     */
    public function handle(Forge $forge)
    {
        $branch = $this->branch;
        $project = $branch->project;

        $forge->setApiKey($project->user->forge_token, null);
        $site = $forge->site($project->forge_server_id, $branch->forge_site_id);

        if ($site->repositoryStatus !== 'installed') {
            $this->release(5);
        }
    }
}
