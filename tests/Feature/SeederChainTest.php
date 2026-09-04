<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Tests\Feature;

use FinnMatti\SeederChain\SeederChain;
use FinnMatti\SeederChain\Support\SeederContext;
use FinnMatti\SeederChain\Tests\Fixtures\Discoverable\TagSeeder;
use FinnMatti\SeederChain\Tests\Fixtures\ExecutionLog;
use FinnMatti\SeederChain\Tests\Fixtures\Seeders\CatalogConsumerSeeder;
use FinnMatti\SeederChain\Tests\Fixtures\Seeders\DepartmentSeeder;
use FinnMatti\SeederChain\Tests\Fixtures\Seeders\OrganizationSeeder;
use FinnMatti\SeederChain\Tests\Fixtures\Seeders\ProjectSeeder;
use FinnMatti\SeederChain\Tests\Fixtures\Seeders\SkippableCatalogSeeder;
use FinnMatti\SeederChain\Tests\Fixtures\Seeders\UserSeeder;
use FinnMatti\SeederChain\Tests\TestCase;

final class SeederChainTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SkippableCatalogSeeder::$skip = false;
        CatalogConsumerSeeder::$fallbackCalls = 0;
    }

    public function test_it_runs_only_the_requested_seeder_when_it_has_no_dependencies(): void
    {
        SeederChain::make()->add(OrganizationSeeder::class)->run();

        self::assertSame([OrganizationSeeder::class], ExecutionLog::$entries);
    }

    public function test_add_and_only_pull_in_dependencies_and_run_in_order(): void
    {
        $order = SeederChain::make()->only(ProjectSeeder::class)->run();

        self::assertSame([
            OrganizationSeeder::class,
            DepartmentSeeder::class,
            UserSeeder::class,
            ProjectSeeder::class,
        ], $order);

        self::assertSame($order, ExecutionLog::$entries);
    }

    public function test_dependent_seeders_can_recall_values_remembered_by_earlier_seeders(): void
    {
        SeederChain::make()->add(ProjectSeeder::class)->run();

        $context = $this->app->make(SeederContext::class);

        $users = $context->get(UserSeeder::class, 'users');
        $projects = $context->get(ProjectSeeder::class, 'projects');

        self::assertSame('Acme Publishing', $users[0]['organization']);
        self::assertSame('Acme Publishing Editorial', $users[0]['department']);
        self::assertSame('Ada', $projects[0]['owner']);
    }

    public function test_plan_reports_the_run_order_without_executing_anything(): void
    {
        $plan = SeederChain::make()->add(DepartmentSeeder::class)->plan();

        self::assertSame([OrganizationSeeder::class, DepartmentSeeder::class], $plan);
        self::assertSame([], ExecutionLog::$entries);
    }

    public function test_discover_finds_chainable_seeders_under_a_directory_and_ignores_the_rest(): void
    {
        SeederChain::make()
            ->discover(
                __DIR__ . '/../Fixtures/Discoverable',
                'FinnMatti\\SeederChain\\Tests\\Fixtures\\Discoverable',
            )
            ->run();

        self::assertSame([TagSeeder::class], ExecutionLog::$entries);
    }

    public function test_a_skippable_seeder_runs_normally_when_shouldskip_is_false(): void
    {
        SkippableCatalogSeeder::$skip = false;

        $order = SeederChain::make()->add(CatalogConsumerSeeder::class)->run();

        self::assertSame($order, ExecutionLog::$entries);
        self::assertContains(SkippableCatalogSeeder::class, ExecutionLog::$entries);
        self::assertSame(0, CatalogConsumerSeeder::$fallbackCalls);

        $context = $this->app->make(SeederContext::class);
        self::assertSame('seeded-by-run', $context->get(CatalogConsumerSeeder::class, 'catalog'));
    }

    public function test_a_skippable_seeder_is_not_run_when_shouldskip_is_true_but_dependents_still_run(): void
    {
        SkippableCatalogSeeder::$skip = true;

        $order = SeederChain::make()->add(CatalogConsumerSeeder::class)->run();

        self::assertSame([SkippableCatalogSeeder::class, CatalogConsumerSeeder::class], $order);
        self::assertSame([CatalogConsumerSeeder::class], ExecutionLog::$entries);
    }

    public function test_recall_falls_back_when_a_skipped_seeder_never_remembered_a_value(): void
    {
        SkippableCatalogSeeder::$skip = true;

        SeederChain::make()->add(CatalogConsumerSeeder::class)->run();

        self::assertSame(1, CatalogConsumerSeeder::$fallbackCalls);

        $context = $this->app->make(SeederContext::class);
        self::assertSame('found-by-fallback', $context->get(CatalogConsumerSeeder::class, 'catalog'));
    }
}
