<?php

namespace Tests\Feature;

use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_cache_miss_populates_cache(): void
    {
        Queue::fake();

        Link::create([
            'slug' => 'miss123',
            'original_url' => 'https://example.com/miss',
        ]);

        $store = Cache::store(config('cache.default'));
        $store->forget('miss123');

        $response = $this->get('/miss123');

        $response->assertRedirect('https://example.com/miss');
        $this->assertSame('https://example.com/miss', $store->get('miss123'));
    }

    public function test_redirect_cache_hit_uses_cached_value(): void
    {
        Queue::fake();

        Link::create([
            'slug' => 'hit123',
            'original_url' => 'https://db.example.com',
        ]);

        $store = Cache::store(config('cache.default'));
        $store->put('hit123', 'https://cache.example.com', 60);

        $response = $this->get('/hit123');

        $response->assertRedirect('https://cache.example.com');
    }

    public function test_redirect_expired_returns_gone(): void
    {
        Queue::fake();

        Link::create([
            'slug' => 'expired123',
            'original_url' => 'https://example.com/expired',
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        $response = $this->get('/expired123');

        $response->assertStatus(410);
    }
}
