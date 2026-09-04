<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Tests\Fixtures\Seeders;

use FinnMatti\SeederChain\Concerns\HasSeederContext;
use FinnMatti\SeederChain\Contracts\ChainableSeeder;
use FinnMatti\SeederChain\Tests\Fixtures\ExecutionLog;
use Illuminate\Database\Seeder;

/**
 * Depends on SkippableCatalogSeeder and recalls its value with a
 * fallback, so tests can assert the fallback is only invoked when
 * SkippableCatalogSeeder actually skipped its run().
 */
final class CatalogConsumerSeeder extends Seeder implements ChainableSeeder
{
    use HasSeederContext;

    public static int $fallbackCalls = 0;

    public static function dependencies(): array
    {
        return [SkippableCatalogSeeder::class];
    }

    public function run(): void
    {
        ExecutionLog::record(self::class);

        $catalog = $this->recall(SkippableCatalogSeeder::class, 'catalog', function (): string {
            self::$fallbackCalls++;

            return 'found-by-fallback';
        });

        $this->remember('catalog', $catalog);
    }
}
