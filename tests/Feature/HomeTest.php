<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function testHomeWelcome()
    {
        $response = $this->get('/');

        $response->assertSuccessful();

        $response->assertSee(config('forest.name'));
    }

    public function testRedirectIfAuth()
    {
        $response = $this->followingRedirects()->get('/login/github');
        $response->assertNotFound();

        $user = User::factory()->create();
        $response = $this->followingRedirects()->actingAs($user)->get('/login/github');
        $response->assertSuccessful();
    }

    public function testHorizonAccess()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        config(['forest.admin' => $user->email]);

        $response = $this->actingAs($user)
            ->get('/horizon');

        $response->assertSuccessful();

        $response = $this->actingAs($anotherUser)
            ->get('/horizon');

        $response->assertForbidden();
    }

    public function testLoginRedirect()
    {
        $response = $this->get('/home');
        $response->assertRedirect('/login/github');

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/home');
        $response->assertSuccessful();
    }
}
