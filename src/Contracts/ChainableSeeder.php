<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Contracts;

/**
 * Implemented by seeders that participate in dependency-ordered chains.
 *
 * A seeder that does not implement this interface is treated as having
 * no dependencies: it will run, but nothing can declare a dependency
 * that resolves to something more specific than "this class exists".
 */
interface ChainableSeeder
{
    /**
     * The seeders that must run, and finish, before this one.
     *
     * @return array<int, class-string<\Illuminate\Database\Seeder>>
     */
    public static function dependencies(): array;
}
