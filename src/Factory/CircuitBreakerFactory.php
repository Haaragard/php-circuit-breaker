<?php

declare(strict_types=1);

namespace Haaragard\CircuitBreaker\Factory;

use Haaragard\CircuitBreaker\Adapter\CacheStorageAdapter;
use Haaragard\CircuitBreaker\Adapter\LocalStorageAdapter;
use Haaragard\CircuitBreaker\Config\CacheConfig;
use Haaragard\CircuitBreaker\Config\Config;
use Haaragard\CircuitBreaker\Contract\CacheConfigInterface;
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
    public function __invoke(): CircuitBreakerInterface
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
        if (! in_array(CircuitBreakerInterface::class, class_implements($serviceClass), true)) {
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

    private function resolveConfig(string $serviceClass, bool $isEnabled, array $config): ConfigInterface|CacheConfigInterface
    {
        return match ($serviceClass) {
            LocalStorageAdapter::class => new Config(
                enabled: $isEnabled,
                timeout: $config['timeout'],
                failureThreshold: $config['failure_threshold'],
                resetTimeout: $config['reset_timeout'],
            ),
            CacheStorageAdapter::class => new CacheConfig(
                enabled: $isEnabled,
                timeout: $config['timeout'],
                failureThreshold: $config['failure_threshold'],
                resetTimeout: $config['reset_timeout'],
                keyPrefix: $config['key_prefix'],
                cacheConnection: $config['cache_connection'],
            ),
            default => throw new InvalidArgumentException("Unsupported service class '{$serviceClass}'."),
        };
    }
}
