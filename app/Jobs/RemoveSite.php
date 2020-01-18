<?php

namespace App\Jobs;

use App\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Themsaid\Forge\Forge;

class RemoveSite implements ShouldQueue
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
     * @param Forge $forge
     * @return void
     */
    public function handle(Forge $forge)
    {
        $branch = $this->branch;
        $project = $branch->project;

        $forge = $forge->setApiKey($project->user->forge_token, null);

        if ($branch->forge_mysql_user_id !== null) {
            $forge->deleteMysqlUser($project->forge_server_id, $branch->forge_mysql_user_id);
        }

        if ($branch->forge_mysql_database_id !== null) {
            $forge->deleteMysqlDatabase($project->forge_server_id, $branch->forge_mysql_database_id);
        }

        $forge->deleteSite($project->forge_server_id, $branch->forge_site_id);

        $branch->githubStatus('success', 'Pull request has been closed.');
    }
}
