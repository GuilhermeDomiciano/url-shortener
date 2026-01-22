<?php

namespace App\Domain\Link;

interface LinkRepository
{
    /**
     * @param array{slug:string, original_url:string, user_id?:int|null, expires_at?:\DateTimeInterface|string|null} $data
     */
    public function create(array $data): Link;

    public function findBySlug(string $slug): ?Link;
}
