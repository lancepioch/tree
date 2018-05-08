<?php

use Faker\Generator as Faker;

$factory->define(App\Project::class, function (Faker $faker) {
    return [
        'forge_site_url' => '*.' . $faker->domainWord . '.' . $faker->domainName,
        'forge_server_id' => $faker->numberBetween(),
        'github_repo' => $faker->domainWord . '/' . $faker->domainWord,
        'webhook_secret' => $faker->sha1,
        'webhook_id' => $faker->numberBetween(),
        'forge_deployment' => "composer require\nphp artisan migrate",
        'forge_deployment_initial' => 'php artisan key:generate',
    ];
});
