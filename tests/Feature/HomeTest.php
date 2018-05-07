<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
}
