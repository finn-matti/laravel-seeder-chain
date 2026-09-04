# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Laravel package (`finn-matti/laravel-seeder-chain`) that adds dependency-ordered
database seeders. Seeders declare what they depend on via a `ChainableSeeder`
interface; `SeederChain` topologically sorts and runs them, pulling in
transitive dependencies automatically. A `SeederContext` singleton lets one
seeder pass values (e.g. created models) to seeders that depend on it, keyed
by `[seeder class][key]`, instead of static properties or re-querying.

See README.md for full usage documentation and the public API table — it's
kept in sync with the code and is the best reference for behavior.

## Commands

```bash
composer install
composer test          # runs phpunit (unit + feature suites)
vendor/bin/phpunit                                   # same as composer test
vendor/bin/phpunit --filter test_method_name          # run a single test
vendor/bin/phpunit tests/Unit/DependencyResolverTest.php   # run one file
```

Tests run against an in-memory SQLite connection via Orchestra Testbench;
fixture seeders don't touch the database, so the suite has no setup beyond
`composer install`.

## Architecture

Four pieces compose the whole package:

- **`SeederChain`** (`src/SeederChain.php`) — the fluent entry point.
  `add()`/`only()` queue seeder class names (aliases of each other — `only()`
  exists just so call sites in tests read as "run only what this needs").
  `discover()` queues everything `SeederDiscovery` finds. `run()` resolves
  order via `DependencyResolver` and calls `run()` on each seeder through the
  container (so seeders can type-hint dependencies in their `run` method,
  same as vanilla Laravel seeders). Before calling `run()`, it checks whether
  the seeder implements `Contracts\SkippableSeeder` and, if `shouldSkip()`
  returns true, skips calling `run()` — the seeder still counts as done for
  ordering, so its dependents still run. `plan()` resolves the order without
  executing, for tests/debugging.

- **`DependencyResolver`** (`src/Support/DependencyResolver.php`) — builds a
  full dependency graph by BFS-walking `dependencies()` from every requested
  seeder (a seeder not implementing `ChainableSeeder` is treated as having
  none), then does an iterative depth-first topological sort
  (`visit()` with a `visiting`/`done` state map) to produce run order.
  Throws `UnresolvedDependencyException` if a declared dependency class
  doesn't exist, `CircularDependencyException` if the `visiting` state is
  re-entered (a cycle). Both checks happen before anything runs.

- **`SeederContext`** (`src/Support/SeederContext.php`) — a singleton bag of
  `[seeder class][key] => value`. `HasSeederContext` (a trait seeders use)
  wraps it with `remember()`/`recall()`/`recalled()` so a seeder always reads
  values from the seeder that produced them, not from ambient state.
  `recall()` accepts an optional fallback `Closure`, invoked (and its result
  remembered) only if the producing seeder never called `remember()` for that
  key — typically because it implemented `SkippableSeeder` and skipped its run.
  `flush()` must be called between test cases (see
  `Testing\InteractsWithSeederChain::flushSeederContext()`) or values leak
  across tests — `TestCase::setUp()` in this repo's own test suite does this
  for the package's own tests.

- **`SeederDiscovery`** (`src/Support/SeederDiscovery.php`) — maps files
  under a directory to FQCNs by stripping the directory prefix and `.php`,
  applying a given root namespace, then filters to classes that
  `class_exists` and implement `ChainableSeeder`. Used by
  `SeederChain::discover()`.

Everything is wired as singletons in `SeederChainServiceProvider` (auto
discovered via `composer.json`'s `extra.laravel.providers`).

`Testing\InteractsWithSeederChain` is a trait for consumers' *own* test
suites (not used internally) — it wraps `SeederChain::make()->add(...)->run()`
as `seedChain()` for seeding only the branch of the dependency graph a given
test needs.

## Working in this repo

- No config or migrations — the package has none to publish.
- `tests/Fixtures/Seeders` and `tests/Fixtures/Discoverable` hold seeder
  fixtures used by `SeederChainTest`/`DependencyResolverTest` and the
  discovery tests respectively; `tests/Fixtures/ExecutionLog.php` is a static
  recorder fixture seeders write to so tests can assert run order.
