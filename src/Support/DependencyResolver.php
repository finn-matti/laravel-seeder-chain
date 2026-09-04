<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Support;

use FinnMatti\SeederChain\Contracts\ChainableSeeder;
use FinnMatti\SeederChain\Exceptions\CircularDependencyException;
use FinnMatti\SeederChain\Exceptions\UnresolvedDependencyException;

/**
 * Turns a list of requested seeders into a run order where every
 * seeder appears after everything it (transitively) depends on.
 */
final class DependencyResolver
{
    /**
     * @param array<int, class-string> $seeders the seeders that were requested
     * @return array<int, class-string> the same seeders plus their dependencies, in run order
     */
    public function resolve(array $seeders): array
    {
        $graph = $this->buildGraph($seeders);

        $sorted = [];
        /** @var array<class-string, 'visiting'|'done'> $state */
        $state = [];
        /** @var array<int, class-string> $path */
        $path = [];

        foreach (array_keys($graph) as $node) {
            $this->visit($node, $graph, $state, $path, $sorted);
        }

        return $sorted;
    }

    /**
     * @param array<int, class-string> $seeders
     * @return array<class-string, array<int, class-string>>
     */
    private function buildGraph(array $seeders): array
    {
        $graph = [];
        $queue = array_values($seeders);

        while ($queue !== []) {
            $seeder = array_shift($queue);

            if (array_key_exists($seeder, $graph)) {
                continue;
            }

            $dependencies = is_a($seeder, ChainableSeeder::class, true)
                ? array_values($seeder::dependencies())
                : [];

            foreach ($dependencies as $dependency) {
                if (! class_exists($dependency)) {
                    throw UnresolvedDependencyException::forMissingClass($seeder, $dependency);
                }
            }

            $graph[$seeder] = $dependencies;
            array_push($queue, ...$dependencies);
        }

        return $graph;
    }

    /**
     * @param array<class-string, array<int, class-string>> $graph
     * @param array<class-string, 'visiting'|'done'> $state
     * @param array<int, class-string> $path
     * @param array<int, class-string> $sorted
     */
    private function visit(string $node, array $graph, array &$state, array &$path, array &$sorted): void
    {
        if (($state[$node] ?? null) === 'done') {
            return;
        }

        if (($state[$node] ?? null) === 'visiting') {
            throw CircularDependencyException::forCycle($node, $path);
        }

        $state[$node] = 'visiting';
        $path[] = $node;

        foreach ($graph[$node] as $dependency) {
            $this->visit($dependency, $graph, $state, $path, $sorted);
        }

        array_pop($path);
        $state[$node] = 'done';
        $sorted[] = $node;
    }
}
