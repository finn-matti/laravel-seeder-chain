<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Concerns;

use Closure;
use FinnMatti\SeederChain\Support\SeederContext;

/**
 * Give a seeder remember()/recall() helpers instead of static properties.
 *
 * A seeder that creates something another seeder will need calls
 * remember() on itself. A dependent seeder calls recall() naming the
 * seeder it depends on and the key that was remembered.
 *
 *     class DepartmentSeeder extends Seeder implements ChainableSeeder
 *     {
 *         use HasSeederContext;
 *
 *         public static function dependencies(): array
 *         {
 *             return [OrganizationSeeder::class];
 *         }
 *
 *         public function run(): void
 *         {
 *             $organization = $this->recall(OrganizationSeeder::class, 'organization');
 *
 *             $department = Department::factory()
 *                 ->for($organization)
 *                 ->create();
 *
 *             $this->remember('department', $department);
 *         }
 *     }
 */
trait HasSeederContext
{
    protected function remember(string $key, mixed $value): mixed
    {
        app(SeederContext::class)->put(static::class, $key, $value);

        return $value;
    }

    /**
     * Read a value remembered by another seeder.
     *
     * If that seeder didn't remember it — typically because it
     * implemented SkippableSeeder and skipped its run() — $fallback
     * is called instead, and its return value is remembered so later
     * recall() calls for the same key don't re-run it. Use this to
     * degrade to a database lookup rather than letting recall() throw,
     * e.g. when a seeder might have already run in an earlier process.
     *
     *     $organization = $this->recall(
     *         OrganizationSeeder::class,
     *         'organization',
     *         fn () => Organization::query()->firstOrFail(),
     *     );
     */
    protected function recall(string $seederClass, string $key, ?Closure $fallback = null): mixed
    {
        $context = app(SeederContext::class);

        if ($fallback !== null && ! $context->has($seederClass, $key)) {
            $context->put($seederClass, $key, $fallback());
        }

        return $context->get($seederClass, $key);
    }

    protected function recalled(string $seederClass, string $key): bool
    {
        return app(SeederContext::class)->has($seederClass, $key);
    }
}
