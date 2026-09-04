<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Tests\Feature;

use FinnMatti\SeederChain\Testing\InteractsWithSeederChain;
use FinnMatti\SeederChain\Tests\Fixtures\ExecutionLog;
use FinnMatti\SeederChain\Tests\Fixtures\Seeders\DepartmentSeeder;
use FinnMatti\SeederChain\Tests\Fixtures\Seeders\OrganizationSeeder;
use FinnMatti\SeederChain\Tests\TestCase;

final class InteractsWithSeederChainTest extends TestCase
{
    use InteractsWithSeederChain;

    public function test_seed_chain_seeds_only_what_the_test_asks_for(): void
    {
        $order = $this->seedChain(DepartmentSeeder::class);

        self::assertSame([OrganizationSeeder::class, DepartmentSeeder::class], $order);
        self::assertSame($order, ExecutionLog::$entries);
    }

    protected function tearDown(): void
    {
        $this->flushSeederContext();

        parent::tearDown();
    }
}
