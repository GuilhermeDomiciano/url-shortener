<?php

namespace App\Application\Link;

use App\Domain\Link\Link;
use App\Domain\Link\LinkRepository;

final class ResolveLinkAction
{
    public function __construct(private LinkRepository $links)
    {
    }

    public function execute(string $slug): ?Link
    {
        return $this->links->findBySlug($slug);
    }
}
