<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;

class HomeTest extends TestCase
{
    public function testHomeWelcome()
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        $response->assertSee(config('app.name'));
    }

    public function testHomeIndex()
    {
        $this->markTestIncomplete();
    }

    public function testHorizonAccess()
    {
        $user = factory(User::class)->create();
        $anotherUser = factory(User::class)->create();

        config(['app.admin' => $user->email]);

        $response = $this->actingAs($user)
            ->get("/horizon");

        $response->assertStatus(200);

        $response = $this->actingAs($anotherUser)
            ->get("/horizon");

        $response->assertStatus(403);
    }

    public function testLoginRedirect()
    {
        $response = $this->get("/home");
        $response->assertRedirect('/login');

        $user = factory(User::class)->create();

        $response = $this->actingAs($user)
            ->get("/home");
        $response->assertStatus(200);

    }
}
