<?php

namespace App\Providers;

use App\Application\Link\LinkCache;
use App\Domain\Link\LinkRepository;
use App\Infrastructure\Cache\RedisLinkCache;
use App\Infrastructure\Persistence\EloquentLinkRepository;
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
        //
    }
}
