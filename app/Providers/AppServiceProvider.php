<?php

namespace App\Providers;

use App\Application\Link\LinkCache;
use App\Domain\Link\LinkRepository;
use App\Infrastructure\Cache\RedisLinkCache;
use App\Infrastructure\Persistence\EloquentLinkRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LinkRepository::class, EloquentLinkRepository::class);
        $this->app->bind(LinkCache::class, RedisLinkCache::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('create-link', function (Request $request) {
            $limit = (int) env('RATE_LIMIT_CREATE_PER_MINUTE', 60);
            return Limit::perMinute($limit)->by($request->ip() ?? 'unknown');
        });

        RateLimiter::for('redirect', function (Request $request) {
            $limit = (int) env('RATE_LIMIT_REDIRECT_PER_MINUTE', 300);
            return Limit::perMinute($limit)->by($request->ip() ?? 'unknown');
        });
    }
}
