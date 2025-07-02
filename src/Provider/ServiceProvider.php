<?php

declare(strict_types=1);

namespace Haaragard\CircuitBreaker\Provider;

use Haaragard\CircuitBreaker\Contract\CircuitBreakerInterface;
use Haaragard\CircuitBreaker\Factory\CircuitBreakerFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../Config/circuit-breaker.php',
            'circuit-breaker'
        );
        $this->app->bind(
            abstract: CircuitBreakerInterface::class,
            concrete: static fn (Application $app) => $app->make(CircuitBreakerFactory::class)()
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../Config/circuit-breaker.php' => config_path('circuit-breaker.php'),
        ], 'haaragard-circuit-breaker-config');
    }
}
