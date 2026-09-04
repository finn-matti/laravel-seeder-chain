<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Tests\Fixtures\Seeders;

use FinnMatti\SeederChain\Contracts\ChainableSeeder;
use Illuminate\Database\Seeder;

final class CircularSeederB extends Seeder implements ChainableSeeder
{
    public static function dependencies(): array
    {
        return [CircularSeederA::class];
    }

    public function run(): void
    {
        // Never reached: the resolver must reject this graph before run().
    }
}
