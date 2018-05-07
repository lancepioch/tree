<?php

use Faker\Generator as Faker;

$factory->define(\App\Branch::class, function (Faker $faker) {
    return [
        'issue_number' => $faker->numberBetween(1, 1000),
        'commit_hash' => $faker->sha1,
        'forge_site_id' => $faker->numberBetween(1, 1000),
        'forge_mysql_user_id' => $faker->numberBetween(1, 1000),
        'forge_mysql_database_id' => $faker->numberBetween(1, 1000),
    ];
});
