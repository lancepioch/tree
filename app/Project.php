<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $casts = [
        'deleted_at' => 'datetime',
        'paused_at' => 'datetime',
        'forge_env_vars' => 'array',
    ];

    protected $fillable = [
        'forge_site_url', 'forge_server_id', 'github_repo', 'webhook_secret', 'forge_deployment', 'forge_deployment_initial', 'webhook_id', 'forge_env_vars',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }
}
