<?php

namespace Tests\Unit\Application\Link;

use App\Application\Link\CreateLinkAction;
use App\Domain\Link\Link;
use App\Domain\Link\LinkRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CreateLinkActionTest extends TestCase
{
    public function test_it_creates_a_link(): void
    {
        $repository = new FakeLinkRepository();
        $action = new CreateLinkAction($repository);

        $expiresAt = new DateTimeImmutable('2030-01-01 00:00:00');
        $link = $action->execute([
            'slug' => 'abc123',
            'original_url' => 'https://example.com',
            'user_id' => 7,
            'expires_at' => $expiresAt,
        ]);

        $this->assertSame('abc123', $link->slug());
        $this->assertSame('https://example.com', $link->originalUrl());
        $this->assertSame(7, $link->userId());
        $this->assertSame($expiresAt->getTimestamp(), $link->expiresAt()?->getTimestamp());
    }
}

final class FakeLinkRepository implements LinkRepository
{
    public function create(array $data): Link
    {
        $expiresAt = $data['expires_at'] instanceof DateTimeImmutable
            ? $data['expires_at']
            : null;

        return new Link(
            1,
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
}
