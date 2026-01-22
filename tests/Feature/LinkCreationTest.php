<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_link(): void
    {
        $payload = [
            'original_url' => 'https://example.com',
            'custom_slug' => 'example123',
        ];

        $response = $this->postJson('/api/links', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'slug' => 'example123',
                'original_url' => 'https://example.com',
            ]);

        $this->assertDatabaseHas('links', [
            'slug' => 'example123',
            'original_url' => 'https://example.com',
        ]);
    }
}
