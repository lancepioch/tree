<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'issue_number', 'forge_site_id', 'forge_mysql_user_id', 'forge_mysql_database_id'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
