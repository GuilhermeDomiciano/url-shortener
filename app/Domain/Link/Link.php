<?php

namespace App\Domain\Link;

use DateTimeImmutable;

final class Link
{
    public function __construct(
        private ?int $id,
        private string $slug,
        private string $originalUrl,
        private ?int $userId = null,
        private ?DateTimeImmutable $expiresAt = null,
        private ?DateTimeImmutable $createdAt = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function originalUrl(): string
    {
        return $this->originalUrl;
    }

    public function userId(): ?int
    {
        return $this->userId;
    }

    public function expiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }
}
