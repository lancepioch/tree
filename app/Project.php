<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $casts = [
        'deleted_at' => 'datetime',
        'paused_at' => 'datetime',
        'forge_env_vars' => 'array',
    ];

    protected $fillable = [
        'forge_site_url', 'forge_server_id', 'github_repo', 'webhook_secret', 'forge_deployment', 'forge_deployment_initial', 'webhook_id', 'forge_env_vars',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function getBranchFromPullRequest(int $number, string $headHash, string $headRef, string $headRepoFullName): Branch
    {
        $branch = $this->branches()
            ->orderBy('id', 'desc')
            ->firstOrNew(['issue_number' => $number]);

        $branch->head_repo = $headRepoFullName;
        $branch->head_ref = $headRef;
        $branch->commit_hash = $headHash;
        $branch->save();

        return $branch;
    }
}
