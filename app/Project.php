<?php

namespace App;

use App\Events\ProjectCreating;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $dispatchesEvents = [
        'creating' => ProjectCreating::class,
    ];

    protected $fillable = [
        'forge_site_url', 'forge_server_id', 'github_repo', 'webhook_secret', 'forge_deployment',
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
