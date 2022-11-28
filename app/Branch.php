<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'issue_number', 'commit_hash', 'forge_site_id', 'forge_mysql_user_id', 'forge_mysql_database_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function githubStatus(string $state, string $description, string $url = null): void
    {
        $project = $this->project;

        $github = $project->user->githubClient();
        [$githubUser, $githubRepo] = explode('/', $project->github_repo);

        $status = [
            'state' => $state,
            'description' => $description,
            'context' => config('app.name'),
        ];

        if ($url !== null) {
            $status['target_url'] = $url;
        }

        $github->repo()->statuses()->create($githubUser, $githubRepo, $this->commit_hash, $status);
    }

    public function githubComment(string $body): void
    {
        $project = $this->project;

        $github = $project->user->githubClient();
        [$githubUser, $githubRepo] = explode('/', $project->github_repo);

        $github->repo()->comments()->create($githubUser, $githubRepo, $this->issue_number, [
            'body' => $body,
        ]);
    }
}
