<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Tests\Fixtures\Seeders;

use FinnMatti\SeederChain\Contracts\ChainableSeeder;
use Illuminate\Database\Seeder;

final class CircularSeederA extends Seeder implements ChainableSeeder
{
    public static function dependencies(): array
    {
        return [CircularSeederB::class];
    }

    public function run(): void
    {
        // Never reached: the resolver must reject this graph before run().
    }
}
