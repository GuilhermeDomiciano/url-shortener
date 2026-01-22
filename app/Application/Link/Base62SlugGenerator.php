<?php

namespace App\Application\Link;

final class Base62SlugGenerator
{
    private const DEFAULT_ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function encode(int $id, ?string $salt = null, int $minLength = 0): string
    {
        if ($id < 0) {
            throw new \InvalidArgumentException('ID must be non-negative.');
        }

        $alphabet = $salt ? $this->shuffledAlphabet($salt) : self::DEFAULT_ALPHABET;
        $base = strlen($alphabet);

        if ($id === 0) {
            $slug = $alphabet[0];
        } else {
            $slug = '';
            $value = $id;

            while ($value > 0) {
                $slug = $alphabet[$value % $base] . $slug;
                $value = intdiv($value, $base);
            }
        }

        if ($minLength > 0 && strlen($slug) < $minLength) {
            $slug = str_repeat($alphabet[0], $minLength - strlen($slug)) . $slug;
        }

        return $slug;
    }

    private function shuffledAlphabet(string $salt): string
    {
        $alphabet = str_split(self::DEFAULT_ALPHABET);
        $hash = hash('sha256', $salt, true);
        $hashLength = strlen($hash);

        for ($i = count($alphabet) - 1; $i > 0; $i--) {
            $hashIndex = $i % $hashLength;
            $swapIndex = ord($hash[$hashIndex]) % ($i + 1);
            [$alphabet[$i], $alphabet[$swapIndex]] = [$alphabet[$swapIndex], $alphabet[$i]];
        }

        return implode('', $alphabet);
    }
}
