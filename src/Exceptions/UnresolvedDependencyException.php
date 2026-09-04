<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Exceptions;

use RuntimeException;

final class UnresolvedDependencyException extends RuntimeException
{
    public static function forMissingClass(string $seeder, string $dependency): self
    {
        return new self(sprintf(
            'Seeder [%s] declares a dependency on [%s], but that class does not exist.',
            $seeder,
            $dependency,
        ));
    }
}
