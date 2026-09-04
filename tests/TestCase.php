<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Tests;

use FinnMatti\SeederChain\SeederChainServiceProvider;
use FinnMatti\SeederChain\Support\SeederContext;
use FinnMatti\SeederChain\Tests\Fixtures\ExecutionLog;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        ExecutionLog::flush();
        $this->app->make(SeederContext::class)->flush();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [SeederChainServiceProvider::class];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
