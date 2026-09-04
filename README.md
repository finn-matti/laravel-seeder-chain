# Laravel Seeder Chain

Dependency-ordered database seeders for Laravel, with a small shared
context so one seeder can hand data to another — instead of static
properties, global state, or re-querying the database.

## The problem this solves

Laravel's `DatabaseSeeder` runs seeders in whatever order you list
them in `$this->call([...])`. That's fine until seeders start
depending on each other's data:

```php
public function run(): void
{
    $this->call([
        OrganizationSeeder::class,
        DepartmentSeeder::class, // needs an Organization
        UserSeeder::class,       // needs an Organization AND a Department
        ProjectSeeder::class,    // needs a User
    ]);
}
```

Two things get harder as this grows:

1. **The order is manual and implicit.** Nothing stops someone from
   moving `UserSeeder` above `DepartmentSeeder` and breaking things
   in a way you only notice at runtime.
2. **Passing data between seeders is awkward.** The usual fixes —
   static properties, `Context::set()`/`Context::get()`, or having
   `UserSeeder` re-query `Department::first()` — all work, but they
   either introduce global state or hide the real dependency.

This package makes the dependency explicit and lets a seeder declare
what it needs, in code, right next to where it uses it.

## Installation

```bash
composer require finn-matti/laravel-seeder-chain
```

The service provider is auto-discovered. No config or migrations to
publish.

## Basic usage

Declare dependencies on a seeder by implementing `ChainableSeeder`,
and use the `HasSeederContext` trait to pass data forward:

```php
use FinnMatti\SeederChain\Concerns\HasSeederContext;
use FinnMatti\SeederChain\Contracts\ChainableSeeder;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder implements ChainableSeeder
{
    use HasSeederContext;

    public static function dependencies(): array
    {
        return [];
    }

    public function run(): void
    {
        $organization = Organization::factory()->create();

        $this->remember('organization', $organization);
    }
}

class DepartmentSeeder extends Seeder implements ChainableSeeder
{
    use HasSeederContext;

    public static function dependencies(): array
    {
        return [OrganizationSeeder::class];
    }

    public function run(): void
    {
        $organization = $this->recall(OrganizationSeeder::class, 'organization');

        $department = Department::factory()
            ->for($organization)
            ->create();

        $this->remember('department', $department);
    }
}
```

Then, instead of `$this->call([...])`, run a chain:

```php
use FinnMatti\SeederChain\SeederChain;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        SeederChain::make()
            ->add(OrganizationSeeder::class, DepartmentSeeder::class, UserSeeder::class, ProjectSeeder::class)
            ->run();
    }
}
```

You don't need to list dependencies yourself, and you don't need to
get the order right. `add()` only needs the seeders you actually care
about — anything they depend on is pulled in and run first
automatically:

```php
// Running just ProjectSeeder also runs Organization, Department,
// and User first, because ProjectSeeder's dependency chain says so.
SeederChain::make()->add(ProjectSeeder::class)->run();
```

`only()` is an alias for `add()` — use whichever name reads better at
the call site. It's typically used in tests, to make "seed just what
this test needs" explicit:

```php
SeederChain::make()->only(ProjectSeeder::class)->run();
```

If a dependency is missing a class, or two seeders depend on each
other, `run()` throws before anything is executed:

- `UnresolvedDependencyException` — a declared dependency class
  doesn't exist.
- `CircularDependencyException` — two or more seeders depend on each
  other, directly or transitively.

## Skipping seeders that have already run

Some seeders shouldn't re-run if their data already exists — a lookup
table, or anything else that isn't safe (or useful) to duplicate.
Implement `SkippableSeeder` alongside `ChainableSeeder`:

```php
use FinnMatti\SeederChain\Contracts\SkippableSeeder;

class VatRateSeeder extends Seeder implements ChainableSeeder, SkippableSeeder
{
    use HasSeederContext;

    public static function dependencies(): array
    {
        return [];
    }

    public function shouldSkip(): bool
    {
        return VatRate::query()->exists();
    }

    public function run(): void
    {
        $rates = VatRate::factory()->count(3)->create();

        $this->remember('rates', $rates);
    }
}
```

`run()` checks `shouldSkip()` before calling `run()` on each seeder. A
skipped seeder still counts as done for ordering purposes — anything
depending on it still runs — but its own `run()` (and thus its
`remember()` calls) is never called. See below for how dependents can
still get a value in that case.

## Falling back when a dependency was skipped

If `VatRateSeeder` above skips, nothing was `remember()`-ed, so a
dependent's plain `recall()` would throw. Pass a fallback closure to
`recall()` to degrade to a database lookup instead of failing:

```php
$rates = $this->recall(
    VatRateSeeder::class,
    'rates',
    fn () => VatRate::query()->get(),
);
```

The fallback only runs when the value wasn't remembered — normally
because that seeder skipped its run() — and its result is stored back
into the context, so later `recall()` calls for the same key don't
invoke it again.

## Auto-discovery

For a large seeder directory, skip listing classes by hand:

```php
SeederChain::make()
    ->discover(database_path('seeders'), 'Database\\Seeders')
    ->run();
```

This finds every class under the directory that implements
`ChainableSeeder` and adds it to the chain. Plain classes and seeders
that don't implement the interface are ignored — they simply won't be
discovered (you can still `add()` them by name if you need them to
run alongside discovered ones).

## Inspecting the plan without running it

```php
$order = SeederChain::make()->add(ProjectSeeder::class)->plan();

// [
//     OrganizationSeeder::class,
//     DepartmentSeeder::class,
//     UserSeeder::class,
//     ProjectSeeder::class,
// ]
```

Useful in a test, or in a console command that prints the resolved
order for debugging a seeder graph.

## Seeding only what a test needs

Running the entire `DatabaseSeeder` in every feature test is slow and
usually creates far more data than the test needs. With
`InteractsWithSeederChain`, a test can seed exactly the branch of the
graph it depends on:

```php
use FinnMatti\SeederChain\Testing\InteractsWithSeederChain;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use InteractsWithSeederChain;

    protected function tearDown(): void
    {
        $this->flushSeederContext();

        parent::tearDown();
    }

    public function test_it_lists_projects_for_the_current_organization(): void
    {
        $this->seedChain(ProjectSeeder::class);

        // Organization, Department, and User were seeded too,
        // because ProjectSeeder depends on them.

        $this->get('/projects')->assertOk();
    }
}
```

Call `flushSeederContext()` in `tearDown()` (or a shared base
`TestCase`) so values remembered in one test can't leak into the
next.

## API reference

| Method | Description |
| --- | --- |
| `SeederChain::make()` | Resolve a chain instance from the container. |
| `->add(string ...$seeders)` | Queue seeders to run. Dependencies are pulled in automatically. |
| `->only(string ...$seeders)` | Alias for `add()`. |
| `->discover(string $directory, string $rootNamespace)` | Queue every `ChainableSeeder` found under a directory. |
| `->plan()` | Return the resolved run order without executing anything. |
| `->run()` | Resolve the order, run each seeder's `run()` method, and return the order. |

| Contract / trait | Description |
| --- | --- |
| `ChainableSeeder::dependencies(): array` | Interface a seeder implements to declare which seeders must run first. |
| `SkippableSeeder::shouldSkip(): bool` | Interface a seeder implements to skip its own `run()` (e.g. its data already exists) without affecting dependency ordering. |
| `HasSeederContext` | Adds `remember(string $key, mixed $value)`, `recall(string $seederClass, string $key, ?Closure $fallback = null)`, and `recalled(string $seederClass, string $key): bool` to a seeder. |
| `Testing\InteractsWithSeederChain` | Adds `seedChain(string ...$seeders)` and `flushSeederContext()` to a test case. |

## How it works

`SeederChain::run()` builds a dependency graph from every requested
seeder's `dependencies()` (a plain seeder that doesn't implement
`ChainableSeeder` is treated as having none), then does a
depth-first topological sort to produce a run order where every
seeder appears after everything it depends on. Seeders shared by
multiple branches only run once, and a seeder implementing
`SkippableSeeder` that returns `true` from `shouldSkip()` is skipped
without affecting the order or its dependents. Values passed with
`remember()` live in a `SeederContext` singleton, keyed by
`[seeder class][key]`, so `recall()` always reads from the seeder that
actually produced the value rather than from ambient global state.

## Testing this package

```bash
composer install
composer test
```

Tests use [Orchestra Testbench](https://packagist.org/packages/orchestra/testbench)
and run against an in-memory SQLite connection; the fixture seeders
themselves don't touch the database, so the suite is fast and has no
setup beyond `composer install`.

## License

MIT.
