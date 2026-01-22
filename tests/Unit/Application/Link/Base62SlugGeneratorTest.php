<?php

namespace Tests\Unit\Application\Link;

use App\Application\Link\Base62SlugGenerator;
use PHPUnit\Framework\TestCase;

final class Base62SlugGeneratorTest extends TestCase
{
    public function test_it_generates_unique_slugs_for_first_1000_ids(): void
    {
        $generator = new Base62SlugGenerator();
        $slugs = [];

        for ($i = 1; $i <= 1000; $i++) {
            $slugs[] = $generator->encode($i, null, 6);
        }

        $this->assertCount(1000, array_unique($slugs));
    }

    public function test_it_generates_consistent_length_with_min_length(): void
    {
        $generator = new Base62SlugGenerator();

        for ($i = 1; $i <= 1000; $i++) {
            $slug = $generator->encode($i, null, 6);
            $this->assertGreaterThanOrEqual(6, strlen($slug));
            $this->assertLessThanOrEqual(10, strlen($slug));
        }
    }

    public function test_it_can_obfuscate_with_salt(): void
    {
        $generator = new Base62SlugGenerator();

        $plain = $generator->encode(12345);
        $salted = $generator->encode(12345, 'secret-salt');

        $this->assertNotSame($plain, $salted);
    }
}
