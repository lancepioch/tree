<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'issue_number' => fake()->numberBetween(1, 1000),
            'commit_hash' => fake()->sha1,
            'forge_site_id' => fake()->numberBetween(1, 1000),
            'forge_mysql_user_id' => fake()->numberBetween(1, 1000),
            'forge_mysql_database_id' => fake()->numberBetween(1, 1000),
        ];
    }
}
