<?php

namespace App\Application\Link;

use App\Domain\Link\Link;
use App\Domain\Link\LinkRepository;

final class CreateLinkAction
{
    public function __construct(private LinkRepository $links)
    {
    }

    /**
     * @param array{slug:string, original_url:string, user_id?:int|null, expires_at?:\DateTimeInterface|string|null} $data
     */
    public function execute(array $data): Link
    {
        return $this->links->create($data);
    }
}
