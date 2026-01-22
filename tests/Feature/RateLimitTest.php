<?php

namespace Tests\Feature;

use App\Models\Link;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_link_rate_limit(): void
    {
        RateLimiter::for('create-link', function (Request $request) {
            return Limit::perMinute(1)->by('test-create');
        });

        $payloadOne = [
            'original_url' => 'https://example.com/a',
            'custom_slug' => 'limit-a',
        ];

        $payloadTwo = [
            'original_url' => 'https://example.com/b',
            'custom_slug' => 'limit-b',
        ];

        $this->postJson('/api/links', $payloadOne)->assertStatus(201);
        $this->postJson('/api/links', $payloadTwo)->assertStatus(429);
    }

    public function test_redirect_rate_limit(): void
    {
        RateLimiter::for('redirect', function (Request $request) {
            return Limit::perMinute(2)->by('test-redirect');
        });

        Link::create([
            'slug' => 'rate123',
            'original_url' => 'https://example.com/rate',
        ]);

        $this->get('/rate123')->assertStatus(302);
        $this->get('/rate123')->assertStatus(302);
        $this->get('/rate123')->assertStatus(429);
    }
}
