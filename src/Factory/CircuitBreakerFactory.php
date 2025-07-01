<?php

declare(strict_types=1);

namespace Haaragard\CircuitBreaker\Factory;

use Haaragard\CircuitBreaker\Adapter\LocalStorageAdapter;
use Haaragard\CircuitBreaker\Config\Config;
use Haaragard\CircuitBreaker\Contract\CircuitBreakerInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;

class CircuitBreakerFactory
{
    public function __construct(protected Application $app)
    {
    }

    /**
     * @throws InvalidArgumentException|BindingResolutionException
     */
    public function invoke(): CircuitBreakerInterface
    {
        $service = config('circuit-breaker.service');
        $serviceConfig = config("circuit-breaker.services.{$service}");
        if (is_null($serviceConfig)) {
            throw new InvalidArgumentException("Service configuration for '{$service}' not found.");
        }

        $serviceClass = $serviceConfig['service'] ?? $this->resolveDefaultLocalStorageAdapter();
        if (! class_exists($serviceClass)) {
            throw new InvalidArgumentException("Service class '{$serviceClass}' does not exist.");
        }
        if (! ($serviceClass instanceof CircuitBreakerInterface::class)) {
            throw new InvalidArgumentException("Service class '{$serviceClass}' must implement " . CircuitBreakerInterface::class);
        }

        return $this->app->make($serviceClass, [
            'config' => new Config(
                enabled: config("circuit-breaker.enabled", false),
                timeout: $serviceConfig['timeout'],
                failureThreshold: $serviceConfig['failure_threshold'],
                resetTimeout: $serviceConfig['reset_timeout'],
            ),
        ]);
    }

    private function resolveDefaultLocalStorageAdapter(): string
    {
        return LocalStorageAdapter::class;
    }
}
