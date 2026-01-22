<?php

namespace Tests\Unit\Application\Link;

use App\Application\Link\ResolveLinkAction;
use App\Domain\Link\Link;
use App\Domain\Link\LinkRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ResolveLinkActionTest extends TestCase
{
    public function test_it_resolves_a_link_by_slug(): void
    {
        $link = new Link(
            10,
            'xyz789',
            'https://example.com/docs',
            null,
            null,
            new DateTimeImmutable('2026-01-01 00:00:00')
        );

        $repository = new FakeResolveLinkRepository($link);
        $action = new ResolveLinkAction($repository);

        $resolved = $action->execute('xyz789');

        $this->assertInstanceOf(Link::class, $resolved);
        $this->assertSame('xyz789', $resolved?->slug());
    }
}

final class FakeResolveLinkRepository implements LinkRepository
{
    public function __construct(private ?Link $link)
    {
    }

    public function create(array $data): Link
    {
        throw new \RuntimeException('Not implemented');
    }

    public function findBySlug(string $slug): ?Link
    {
        return $this->link && $this->link->slug() === $slug ? $this->link : null;
    }
}
