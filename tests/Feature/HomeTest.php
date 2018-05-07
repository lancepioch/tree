<?php

namespace Tests\Feature;

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
}
