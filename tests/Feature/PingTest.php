<?php

namespace Tests\Feature;

use Tests\TestCase;

class PingTest extends TestCase
{
    public function test_ping_returns_200_with_pong_true(): void
    {
        $response = $this->getJson('/api/ping');

        $response->assertStatus(200)
            ->assertExactJson(['pong' => true]);
    }

    public function test_ping_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/ping');

        $response->assertStatus(200);
    }
}
