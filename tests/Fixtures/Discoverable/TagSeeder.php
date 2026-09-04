<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Tests\Fixtures\Discoverable;

use FinnMatti\SeederChain\Contracts\ChainableSeeder;
use FinnMatti\SeederChain\Tests\Fixtures\ExecutionLog;
use Illuminate\Database\Seeder;

final class TagSeeder extends Seeder implements ChainableSeeder
{
    public static function dependencies(): array
    {
        return [];
    }

    public function run(): void
    {
        ExecutionLog::record(self::class);
    }
}
