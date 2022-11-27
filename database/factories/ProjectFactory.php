<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'forge_site_url' => '*.' . fake()->domainWord . '.' . fake()->domainName,
            'forge_server_id' => fake()->numberBetween(),
            'github_repo' => fake()->domainWord . '/' . fake()->domainWord,
            'webhook_secret' => fake()->sha1,
            'webhook_id' => fake()->numberBetween(),
            'forge_deployment' => "composer require\nphp artisan migrate",
            'forge_deployment_initial' => 'php artisan key:generate',
            'paused_at' => null,
        ];
    }
}
