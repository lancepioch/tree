<?php

namespace App;

use Github\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'forge_token', 'github_token', 'github_id',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function githubClient(): Client
    {
        $github = new Client();
        $github->authenticate($this->github_token, null, Client::AUTH_ACCESS_TOKEN);

        return $github;
    }
}
