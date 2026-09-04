<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Testing;

use FinnMatti\SeederChain\SeederChain;
use FinnMatti\SeederChain\Support\SeederContext;

/**
 * Mix into a feature test to seed exactly what that test needs.
 *
 *     $this->seedChain(OrderSeeder::class);
 *
 * pulls in OrderSeeder's dependencies too, in the right order, without
 * running your whole DatabaseSeeder. Call flushSeederContext() in
 * tearDown() (or a base TestCase) so remembered values from one test
 * never leak into the next.
 */
trait InteractsWithSeederChain
{
    /**
     * @return array<int, class-string> the order the seeders ran in
     */
    protected function seedChain(string ...$seeders): array
    {
        return SeederChain::make()->add(...$seeders)->run();
    }

    protected function flushSeederContext(): void
    {
        $this->app->make(SeederContext::class)->flush();
    }
}
