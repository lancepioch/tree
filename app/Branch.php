<?php

namespace App;

use Github\Client;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'issue_number', 'commit_hash', 'forge_site_id', 'forge_mysql_user_id', 'forge_mysql_database_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function githubStatus($state, $description, $url = null)
    {
        $project = $this->project;

        $github = new Client();
        $github->authenticate($project->user->github_token, null, Client::AUTH_HTTP_PASSWORD);
        [$githubUser, $githubRepo] = explode('/', $project->github_repo);

        $status = [
            'state' => $state,
            'description' => $description,
            'context' => config('app.name'),
        ];

        if ($url !== null) {
            $status['target_url'] = $url;
        }

        $github->api('repo')->statuses()->create($githubUser, $githubRepo, $this->commit_hash, $status);
    }
}
