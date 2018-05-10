<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function testHomeWelcome()
    {
        $response = $this->get('/');

        $response->assertSuccessful();

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
            ->get('/horizon');

        $response->assertSuccessful();

        $response = $this->actingAs($anotherUser)
            ->get('/horizon');

        $response->assertForbidden();
    }

    public function testLoginRedirect()
    {
        $response = $this->get('/home');
        $response->assertRedirect('/login');

        $user = factory(User::class)->create();
        $response = $this->actingAs($user)->get('/home');
        $response->assertSuccessful();
    }
}
