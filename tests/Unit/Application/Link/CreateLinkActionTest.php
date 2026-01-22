<?php

namespace Tests\Unit\Application\Link;

use App\Application\Link\Base62SlugGenerator;
use App\Application\Link\CreateLinkAction;
use App\Application\Link\LinkCache;
use App\Domain\Link\Link;
use App\Domain\Link\LinkRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CreateLinkActionTest extends TestCase
{
    public function test_it_creates_a_link(): void
    {
        $repository = new FakeLinkRepository();
        $cache = new FakeLinkCache();
        $action = new CreateLinkAction($repository, new Base62SlugGenerator(), $cache);

        $expiresAt = new DateTimeImmutable('2030-01-01 00:00:00');
        $link = $action->execute([
            'original_url' => 'https://example.com',
            'custom_slug' => 'abc123',
            'user_id' => 7,
            'expires_at' => $expiresAt,
        ]);

        $this->assertSame('abc123', $link->slug());
        $this->assertSame('https://example.com', $link->originalUrl());
        $this->assertSame(7, $link->userId());
        $this->assertSame($expiresAt->getTimestamp(), $link->expiresAt()?->getTimestamp());
        $this->assertSame('abc123', $cache->lastSlug);
    }
}

final class FakeLinkRepository implements LinkRepository
{
    private int $nextId = 1;

    public function create(array $data): Link
    {
        $expiresAt = $data['expires_at'] instanceof DateTimeImmutable ? $data['expires_at'] : null;

        return new Link(
            $this->nextId++,
            $data['slug'],
            $data['original_url'],
            $data['user_id'] ?? null,
            $expiresAt,
            new DateTimeImmutable('2026-01-01 00:00:00')
        );
    }

    public function findBySlug(string $slug): ?Link
    {
        return null;
    }

    public function updateSlug(int $id, string $slug): Link
    {
        return new Link(
            $id,
            $slug,
            'https://example.com',
            7,
            null,
            new DateTimeImmutable('2026-01-01 00:00:00')
        );
    }
}

final class FakeLinkCache implements LinkCache
{
    public ?string $lastSlug = null;

    public function put(string $slug, string $originalUrl, ?\DateTimeInterface $expiresAt): void
    {
        $this->lastSlug = $slug;
    }
}
