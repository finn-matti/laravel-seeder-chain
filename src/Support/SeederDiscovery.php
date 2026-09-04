<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Support;

use FinnMatti\SeederChain\Contracts\ChainableSeeder;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

/**
 * Finds every ChainableSeeder class under a directory, so a chain can
 * be built without hand-maintaining a list of add() calls.
 */
final class SeederDiscovery
{
    /**
     * @return array<int, class-string<ChainableSeeder>>
     */
    public function in(string $directory, string $rootNamespace): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $classes = [];
        $directory = rtrim($directory, '/\\');

        foreach ((new Finder())->files()->name('*.php')->in($directory) as $file) {
            $relative = Str::of($file->getPathname())
                ->after($directory . DIRECTORY_SEPARATOR)
                ->beforeLast('.php')
                ->replace(DIRECTORY_SEPARATOR, '\\');

            $class = rtrim($rootNamespace, '\\') . '\\' . $relative;

            if (class_exists($class) && is_a($class, ChainableSeeder::class, true)) {
                $classes[] = $class;
            }
        }

        sort($classes);

        return $classes;
    }
}
