<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Tests\Unit;

use FinnMatti\SeederChain\Exceptions\CircularDependencyException;
use FinnMatti\SeederChain\Exceptions\UnresolvedDependencyException;
use FinnMatti\SeederChain\Support\DependencyResolver;
use FinnMatti\SeederChain\Tests\Fixtures\Seeders\CircularSeederA;
use FinnMatti\SeederChain\Tests\Fixtures\Seeders\DepartmentSeeder;
use FinnMatti\SeederChain\Tests\Fixtures\Seeders\MissingDependencySeeder;
use FinnMatti\SeederChain\Tests\Fixtures\Seeders\OrganizationSeeder;
use FinnMatti\SeederChain\Tests\Fixtures\Seeders\PlainSeeder;
use FinnMatti\SeederChain\Tests\Fixtures\Seeders\ProjectSeeder;
use FinnMatti\SeederChain\Tests\Fixtures\Seeders\UserSeeder;
use FinnMatti\SeederChain\Tests\TestCase;

final class DependencyResolverTest extends TestCase
{
    public function test_it_orders_a_single_seeder_with_no_dependencies(): void
    {
        $order = (new DependencyResolver())->resolve([OrganizationSeeder::class]);

        self::assertSame([OrganizationSeeder::class], $order);
    }

    public function test_it_pulls_in_transitive_dependencies_not_explicitly_requested(): void
    {
        $order = (new DependencyResolver())->resolve([ProjectSeeder::class]);

        self::assertSame([
            OrganizationSeeder::class,
            DepartmentSeeder::class,
            UserSeeder::class,
            ProjectSeeder::class,
        ], $order);
    }

    public function test_it_only_lists_a_shared_dependency_once(): void
    {
        $order = (new DependencyResolver())->resolve([
            DepartmentSeeder::class,
            UserSeeder::class,
        ]);

        self::assertSame([
            OrganizationSeeder::class,
            DepartmentSeeder::class,
            UserSeeder::class,
        ], $order);
    }

    public function test_a_plain_seeder_is_treated_as_having_no_dependencies(): void
    {
        $order = (new DependencyResolver())->resolve([PlainSeeder::class, OrganizationSeeder::class]);

        self::assertSame([PlainSeeder::class, OrganizationSeeder::class], $order);
    }

    public function test_it_throws_on_a_circular_dependency(): void
    {
        $this->expectException(CircularDependencyException::class);
        $this->expectExceptionMessage('Circular seeder dependency detected');

        (new DependencyResolver())->resolve([CircularSeederA::class]);
    }

    public function test_it_throws_when_a_declared_dependency_class_does_not_exist(): void
    {
        $this->expectException(UnresolvedDependencyException::class);
        $this->expectExceptionMessage('does not exist');

        (new DependencyResolver())->resolve([MissingDependencySeeder::class]);
    }
}
