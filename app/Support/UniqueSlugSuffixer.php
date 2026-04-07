<?php

namespace App\Support;

use RuntimeException;
use Illuminate\Support\Str;

class UniqueSlugSuffixer
{
    public static function ensureUnique(string $base, callable $exists): string
    {
        $base = Str::slug($base) ?: 'item';

        if (! $exists($base)) {
            return $base;
        }

        $length = max(1, (int) config('lotg.random_token_length', 4));
        $maxAttempts = max(1, (int) config('lotg.random_token_max_attempts', 3));

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $candidate = $base.'-'.Str::lower(Str::random($length));

            if (! $exists($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('Unable to generate a unique slug/code after the configured retry limit.');
    }
}
