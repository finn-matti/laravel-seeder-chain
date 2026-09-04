<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Tests\Fixtures\Discoverable;

/**
 * Lives in the same directory as TagSeeder so discovery tests can
 * confirm it is correctly skipped: it's a PHP class, but not a
 * ChainableSeeder.
 */
final class NotASeeder
{
}
