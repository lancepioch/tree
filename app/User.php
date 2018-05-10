<?php

namespace App;

use Github\Client;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'forge_token', 'github_token', 'github_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function githubClient()
    {
        $github = new Client();
        $github->authenticate($this->github_token, null, Client::AUTH_HTTP_PASSWORD);

        return $github;
    }
}
