<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Test that GET /health returns HTTP 200 with JSON {"status":"ok"}.
     */
    public function test_health_endpoint_returns_200_with_status_ok(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
        $response->assertExactJson(['status' => 'ok']);
    }

    /**
     * Test that the Content-Type header is application/json.
     */
    public function test_health_endpoint_returns_json_content_type(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }
}
