<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain;

use FinnMatti\SeederChain\Contracts\SkippableSeeder;
use FinnMatti\SeederChain\Support\DependencyResolver;
use FinnMatti\SeederChain\Support\SeederDiscovery;
use Illuminate\Contracts\Container\Container;

/**
 * Collects the seeders you want to run, works out an order that
 * respects every declared dependency, and runs them.
 *
 *     SeederChain::make()
 *         ->add(OrganizationSeeder::class, DepartmentSeeder::class, UserSeeder::class)
 *         ->run();
 *
 * You only need to add() the seeders you care about — their
 * dependencies (and dependencies-of-dependencies) are pulled in and
 * run first automatically, so add() and only() behave the same way.
 * only() exists purely to make call sites read as "run this feature's
 * data", e.g. inside a feature test.
 */
final class SeederChain
{
    /** @var array<int, class-string> */
    private array $seeders = [];

    public function __construct(
        private readonly Container $container,
        private readonly DependencyResolver $resolver,
        private readonly SeederDiscovery $discovery,
    ) {
    }

    public static function make(): self
    {
        return app(self::class);
    }

    /**
     * Queue seeders to run. Anything they depend on is pulled in
     * automatically — you don't need to list dependencies yourself.
     */
    public function add(string ...$seeders): self
    {
        array_push($this->seeders, ...$seeders);

        return $this;
    }

    /**
     * Alias for add(), for call sites where "only run what this needs"
     * reads better than "add" — typically in tests.
     */
    public function only(string ...$seeders): self
    {
        return $this->add(...$seeders);
    }

    /**
     * Queue every ChainableSeeder found under a directory.
     */
    public function discover(string $directory, string $rootNamespace): self
    {
        return $this->add(...$this->discovery->in($directory, $rootNamespace));
    }

    /**
     * Resolve the run order and execute each seeder's run() method.
     *
     * A seeder implementing SkippableSeeder is asked shouldSkip()
     * before running: if it returns true, run() is not called, but
     * the seeder still counts as done, so anything depending on it
     * still runs.
     *
     * @return array<int, class-string> the order the seeders ran in
     */
    public function run(): array
    {
        $order = $this->resolver->resolve($this->seeders);

        foreach ($order as $seederClass) {
            $seeder = $this->container->make($seederClass);

            if ($seeder instanceof SkippableSeeder && $seeder->shouldSkip()) {
                continue;
            }

            $this->container->call([$seeder, 'run']);
        }

        return $order;
    }

    /**
     * Resolve the run order without executing anything — useful for
     * asserting on ordering, or for debugging a dependency graph.
     *
     * @return array<int, class-string>
     */
    public function plan(): array
    {
        return $this->resolver->resolve($this->seeders);
    }
}
