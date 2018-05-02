<?php

namespace App\Jobs;

use App\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Themsaid\Forge\Forge;

class RemoveSite implements ShouldQueue
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

        $branch = $project
            ->branches()
            ->where('issue_number', $pullRequest['number'])
            ->orderBy('id', 'desc')
            ->first();

        if ($branch === null) {
            return;
        }

        if ($branch->forge_mysql_user_id !== null) {
            $forge->deleteMysqlUser($project->forge_server_id, $branch->forge_mysql_user_id);
        }

        if ($branch->forge_mysql_database_id !== null) {
            $forge->deleteMysqlDatabase($project->forge_server_id, $branch->forge_mysql_database_id);
        }

        $forge->deleteSite($project->forge_server_id, $branch->forge_site_id);
    }
}
