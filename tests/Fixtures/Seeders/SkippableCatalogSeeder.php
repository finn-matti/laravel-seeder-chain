<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Tests\Fixtures\Seeders;

use FinnMatti\SeederChain\Concerns\HasSeederContext;
use FinnMatti\SeederChain\Contracts\ChainableSeeder;
use FinnMatti\SeederChain\Contracts\SkippableSeeder;
use FinnMatti\SeederChain\Tests\Fixtures\ExecutionLog;
use Illuminate\Database\Seeder;

/**
 * A seeder whose shouldSkip() is controlled by a static flag, so tests
 * can exercise both the "already seeded, skip run()" and "not seeded
 * yet, run normally" branches.
 */
final class SkippableCatalogSeeder extends Seeder implements ChainableSeeder, SkippableSeeder
{
    use HasSeederContext;

    public static bool $skip = false;

    public static function dependencies(): array
    {
        return [];
    }

    public function shouldSkip(): bool
    {
        return self::$skip;
    }

    public function run(): void
    {
        ExecutionLog::record(self::class);

        $this->remember('catalog', 'seeded-by-run');
    }
}
