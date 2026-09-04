<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Support;

use RuntimeException;

/**
 * A request-lifetime bag of values keyed by [seeder class][key].
 *
 * This replaces static properties, global state, or re-querying the
 * database when one seeder needs a record another seeder just created.
 * Registered as a singleton by the service provider, so every seeder
 * run in the same chain (or the same test) shares one instance.
 */
final class SeederContext
{
    /** @var array<class-string, array<string, mixed>> */
    private array $items = [];

    public function put(string $seeder, string $key, mixed $value): void
    {
        $this->items[$seeder][$key] = $value;
    }

    public function get(string $seeder, string $key): mixed
    {
        if (! $this->has($seeder, $key)) {
            throw new RuntimeException(sprintf(
                'No value remembered for key [%s] by seeder [%s]. '
                . 'Make sure [%s] has run and called remember(\'%s\', ...) before this seeder recalls it.',
                $key,
                $seeder,
                $seeder,
                $key,
            ));
        }

        return $this->items[$seeder][$key];
    }

    public function has(string $seeder, string $key): bool
    {
        return array_key_exists($seeder, $this->items)
            && array_key_exists($key, $this->items[$seeder]);
    }

    public function forget(string $seeder): void
    {
        unset($this->items[$seeder]);
    }

    /**
     * Drop everything. Call this between test cases so leftover values
     * from one test cannot leak into the next.
     */
    public function flush(): void
    {
        $this->items = [];
    }
}
