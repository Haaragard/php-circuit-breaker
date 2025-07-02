<?php

declare(strict_types=1);

namespace Haaragard\CircuitBreaker\Provider;

use Haaragard\CircuitBreaker\Contract\CircuitBreakerInterface;
use Haaragard\CircuitBreaker\Factory\CircuitBreakerFactory;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->bind(
            abstract: CircuitBreakerInterface::class,
            concrete: CircuitBreakerFactory::class
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/circuit-breaker.php' => config_path('circuit-breaker.php'),
        ], 'config');

        $this->mergeConfigFrom(
            __DIR__ . '/../Config/circuit-breaker.php',
            'circuit-breaker'
        );
    }

    public function provides(): array
    {
        return [
            CircuitBreakerInterface::class => CircuitBreakerFactory::class,
        ];
    }
}
