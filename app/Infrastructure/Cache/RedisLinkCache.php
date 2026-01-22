<?php

namespace App\Infrastructure\Cache;

use App\Application\Link\LinkCache;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

final class RedisLinkCache implements LinkCache
{
    public function put(string $slug, string $originalUrl, ?DateTimeInterface $expiresAt): void
    {
        $store = Cache::store(config('cache.default'));

        if ($expiresAt === null) {
            $store->forever($slug, $originalUrl);
            return;
        }

        $ttl = Carbon::now()->diffInSeconds(Carbon::instance($expiresAt), false);

        if ($ttl <= 0) {
            return;
        }

        $store->put($slug, $originalUrl, $ttl);
    }
}
