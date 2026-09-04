<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Tests\Fixtures;

/**
 * Records the order fixture seeders actually ran in, so tests can
 * assert on it without needing a real database.
 */
final class ExecutionLog
{
    /** @var array<int, class-string> */
    public static array $entries = [];

    public static function record(string $seeder): void
    {
        self::$entries[] = $seeder;
    }

    public static function flush(): void
    {
        self::$entries = [];
    }
}
