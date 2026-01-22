<?php

namespace App\Application\Link;

use App\Domain\Link\Link;
use App\Domain\Link\LinkRepository;
use Illuminate\Support\Str;

final class CreateLinkAction
{
    public function __construct(
        private LinkRepository $links,
        private Base62SlugGenerator $slugGenerator,
        private LinkCache $cache
    ) {
    }

    /**
     * @param array{original_url:string, custom_slug?:string|null, user_id?:int|null, expires_at?:\DateTimeInterface|null} $data
     */
    public function execute(array $data): Link
    {
        $customSlug = $data['custom_slug'] ?? null;
        $expiresAt = $data['expires_at'] ?? null;

        if ($customSlug) {
            $link = $this->links->create([
                'slug' => $customSlug,
                'original_url' => $data['original_url'],
                'user_id' => $data['user_id'] ?? null,
                'expires_at' => $expiresAt,
            ]);

            $this->cache->put($link->slug(), $link->originalUrl(), $expiresAt);

            return $link;
        }

        $placeholder = 'tmp_' . Str::ulid();
        $link = $this->links->create([
            'slug' => $placeholder,
            'original_url' => $data['original_url'],
            'user_id' => $data['user_id'] ?? null,
            'expires_at' => $expiresAt,
        ]);

        $slug = $this->slugGenerator->encode($link->id() ?? 0, null, 6);
        $link = $this->links->updateSlug($link->id() ?? 0, $slug);

        $this->cache->put($link->slug(), $link->originalUrl(), $expiresAt);

        return $link;
    }
}
