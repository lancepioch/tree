<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function testProfileUpdate()
    {
        $response = $this->post('/user/update', ['forge_token' => 'ham']);
        $response->assertRedirect('/login/github');

        $user = factory(User::class)->create();
        $response = $this->actingAs($user)->post('/user/update', ['forge_token' => 'ham']);
        $response->assertRedirect('/home');
        $this->assertSame($user->forge_token, 'ham');
    }
}
