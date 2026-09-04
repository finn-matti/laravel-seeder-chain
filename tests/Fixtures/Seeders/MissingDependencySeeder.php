<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Tests\Fixtures\Seeders;

use FinnMatti\SeederChain\Contracts\ChainableSeeder;
use Illuminate\Database\Seeder;

final class MissingDependencySeeder extends Seeder implements ChainableSeeder
{
    public static function dependencies(): array
    {
        return ['FinnMatti\\SeederChain\\Tests\\Fixtures\\Seeders\\DoesNotExistSeeder'];
    }

    public function run(): void
    {
        // Never reached: the resolver must reject this graph before run().
    }
}
