<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Tests\Fixtures\Seeders;

use FinnMatti\SeederChain\Tests\Fixtures\ExecutionLog;
use Illuminate\Database\Seeder;

/**
 * A plain seeder that does not implement ChainableSeeder. It should
 * still run fine inside a chain — it's just treated as having no
 * dependencies and is never discovered by SeederDiscovery.
 */
final class PlainSeeder extends Seeder
{
    public function run(): void
    {
        ExecutionLog::record(self::class);
    }
}
