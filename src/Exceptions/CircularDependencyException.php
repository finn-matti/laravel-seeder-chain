<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Exceptions;

use RuntimeException;

final class CircularDependencyException extends RuntimeException
{
    /**
     * @param array<int, class-string> $path the chain of classes leading back to $seeder
     */
    public static function forCycle(string $seeder, array $path): self
    {
        return new self(sprintf(
            'Circular seeder dependency detected: %s -> %s.',
            implode(' -> ', $path),
            $seeder,
        ));
    }
}
