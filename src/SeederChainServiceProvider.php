<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain;

use FinnMatti\SeederChain\Support\DependencyResolver;
use FinnMatti\SeederChain\Support\SeederContext;
use FinnMatti\SeederChain\Support\SeederDiscovery;
use Illuminate\Support\ServiceProvider;

final class SeederChainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SeederContext::class);
        $this->app->singleton(DependencyResolver::class);
        $this->app->singleton(SeederDiscovery::class);

        $this->app->singleton(SeederChain::class, static fn ($app) => new SeederChain(
            $app,
            $app->make(DependencyResolver::class),
            $app->make(SeederDiscovery::class),
        ));
    }
}
