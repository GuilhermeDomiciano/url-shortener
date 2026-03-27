<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_returns_200_when_db_and_redis_are_ok(): void
    {
        DB::shouldReceive('select')
            ->once()
            ->with('SELECT 1')
            ->andReturn([]);

        Redis::shouldReceive('ping')
            ->once()
            ->andReturn(true);

        $response = $this->getJson('/health');

        $response->assertStatus(200)
                 ->assertJsonStructure(['status', 'timestamp'])
                 ->assertJsonPath('status', 'ok');
    }

    public function test_health_returns_503_when_db_fails(): void
    {
        DB::shouldReceive('select')
            ->once()
            ->with('SELECT 1')
            ->andThrow(new \Exception('Connection refused'));

        Redis::shouldReceive('ping')
            ->once()
            ->andReturn(true);

        $response = $this->getJson('/health');

        $response->assertStatus(503)
                 ->assertJsonStructure(['status', 'timestamp', 'services'])
                 ->assertJsonPath('status', 'error')
                 ->assertJsonPath('services.database', 'error')
                 ->assertJsonPath('services.redis', 'ok');
    }

    public function test_health_returns_503_when_redis_fails(): void
    {
        DB::shouldReceive('select')
            ->once()
            ->with('SELECT 1')
            ->andReturn([]);

        Redis::shouldReceive('ping')
            ->once()
            ->andThrow(new \Exception('Connection refused'));

        $response = $this->getJson('/health');

        $response->assertStatus(503)
                 ->assertJsonStructure(['status', 'timestamp', 'services'])
                 ->assertJsonPath('status', 'error')
                 ->assertJsonPath('services.database', 'ok')
                 ->assertJsonPath('services.redis', 'error');
    }
}
