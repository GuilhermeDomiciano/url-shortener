<?php

namespace App\Application\Link;

use App\Models\Link as LinkModel;
use Illuminate\Support\Facades\Cache;

final class DeactivateLinkAction
{
    /**
     * Soft-deletes a link by slug and invalidates its Redis cache entries.
     *
     * @throws \RuntimeException  if the link is not found
     */
    public function execute(string $slug): void
    {
        /** @var LinkModel|null $model */
        $model = LinkModel::query()->where('slug', $slug)->first();

        if ($model === null) {
            throw new \RuntimeException("Link not found: {$slug}");
        }

        $model->delete();

        // Invalidate redirect cache key (stored directly by slug in the cache store)
        Cache::forget($slug);

        // Invalidate analytics cache keys for all periods
        foreach (['7d', '30d', 'all'] as $period) {
            Cache::forget("analytics:{$slug}:{$period}");
        }
    }
}
