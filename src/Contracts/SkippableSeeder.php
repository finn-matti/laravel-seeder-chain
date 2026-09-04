<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Contracts;

/**
 * Implemented by seeders that can detect their own data already exists
 * and skip re-seeding it — e.g. a lookup-table seeder that shouldn't
 * duplicate rows if it runs twice.
 *
 * Skipping is independent of dependency resolution: a skipped seeder
 * still counts as "done" for ordering purposes, so seeders that depend
 * on it still run. It only decides whether run() itself is called.
 */
interface SkippableSeeder
{
    /**
     * Return true if this seeder's data already exists and run()
     * should not be called.
     */
    public function shouldSkip(): bool;
}
