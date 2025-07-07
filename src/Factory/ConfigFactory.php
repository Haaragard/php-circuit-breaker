<?php

declare(strict_types=1);

namespace Haaragard\CircuitBreaker\Factory;

use Haaragard\CircuitBreaker\Adapter\CacheStorageAdapter;
use Haaragard\CircuitBreaker\Adapter\LocalStorageAdapter;
use Haaragard\CircuitBreaker\Config\CacheConfig;
use Haaragard\CircuitBreaker\Config\Config;
use Haaragard\CircuitBreaker\Contract\ConfigInterface;
use InvalidArgumentException;

class ConfigFactory
{
    public function __invoke(
        string $serviceClass,
        bool $isEnabled,
        array $config
    ): ConfigInterface {
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
            default => $this->resolveCustomConfig($isEnabled, $config),
        };
    }

    private function resolveCustomConfig(bool $isEnabled, array $config): ConfigInterface
    {
        $configClass = $config['config'] ?? null;
        if (is_null($configClass) || ! class_exists($configClass)) {
            throw new InvalidArgumentException(
                "Configuration class '{$configClass}' does not exist."
            );
        }

        unset($config['config'], $config['service']);

        $configWithKeysCammel = $this->convertKeysToCamelCase($config);
        $configWithKeysCammel['enabled'] = $isEnabled;

        return new $configClass(...$configWithKeysCammel);
    }

    private function convertKeysToCamelCase(array $array): array
    {
        if (empty($array)) {
            return [];
        }

        return array_combine(
            array_map(
                static fn ($key) => lcfirst(
                    str_replace('_', '', ucwords($key, '_'))
                ),
                array_keys($array)
            ),
            array_values($array)
        );
    }
}
