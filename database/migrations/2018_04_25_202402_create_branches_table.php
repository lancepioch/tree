<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBranchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('project_id');
            $table->integer('issue_number');
            $table->string('commit_hash');
            $table->string('head_repo');
            $table->string('head_ref');
            $table->integer('forge_site_id')->nullable();
            $table->integer('forge_mysql_user_id')->nullable();
            $table->integer('forge_mysql_database_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('branches');
    }
}
