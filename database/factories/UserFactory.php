<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => fake()->name,
            'email' => fake()->unique()->safeEmail,
            'forge_token' => fake()->sha256,
            'github_token' => fake()->sha1,
            'github_id' => fake()->numberBetween(),
        ];
    }
}
