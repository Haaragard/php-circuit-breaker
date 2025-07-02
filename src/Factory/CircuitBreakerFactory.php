<?php

declare(strict_types=1);

namespace Haaragard\CircuitBreaker\Factory;

use Haaragard\CircuitBreaker\Adapter\LocalStorageAdapter;
use Haaragard\CircuitBreaker\Config\Config;
use Haaragard\CircuitBreaker\Contract\CircuitBreakerInterface;
use Haaragard\CircuitBreaker\Contract\ConfigInterface;
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
        if (! (get_class($serviceClass) instanceof CircuitBreakerInterface)) {
            throw new InvalidArgumentException("Service class '{$serviceClass}' must implement " . CircuitBreakerInterface::class);
        }

        $isCircuitBreakerEnabled = config('circuit-breaker.enabled', false);

        return $this->app->make($serviceClass, [
            'config' => $this->resolveConfig(
                $serviceClass,
                $isCircuitBreakerEnabled,
                $serviceConfig
            ),
        ]);
    }

    private function resolveDefaultLocalStorageAdapter(): string
    {
        return LocalStorageAdapter::class;
    }

    private function resolveConfig(string $serviceClass, bool $isEnabled, array $config): ConfigInterface
    {
        return match ($serviceClass) {
            LocalStorageAdapter::class => new Config(
                enabled: $isEnabled,
                timeout: $config['timeout'],
                failureThreshold: $config['failure_threshold'],
                resetTimeout: $config['reset_timeout'],
            ),
            default => throw new InvalidArgumentException("Unsupported service class '{$serviceClass}'."),
        };
    }
}
