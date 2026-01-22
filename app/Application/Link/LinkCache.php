<?php

namespace App\Application\Link;

use DateTimeInterface;

interface LinkCache
{
    public function put(string $slug, string $originalUrl, ?DateTimeInterface $expiresAt): void;
}
