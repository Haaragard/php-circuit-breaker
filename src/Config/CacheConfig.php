<?php

declare(strict_types=1);

namespace Haaragard\CircuitBreaker\Config;

use Haaragard\CircuitBreaker\Contract\CacheConfigInterface;
use InvalidArgumentException;

class CacheConfig extends Config implements CacheConfigInterface
{
    public function __construct(
        protected bool $enabled,
        protected int $timeout,
        protected int $failureThreshold,
        protected int $resetTimeout,
        protected string $keyPrefix = 'circuit-breaker:',
        protected string $cacheConnection = 'default'
    ) {
        parent::__construct(
            $enabled,
            $timeout,
            $failureThreshold,
            $resetTimeout,
        );

        $this->validate();
    }

    protected function validate(): void
    {
        if (empty($this->cacheConnection)) {
            throw new InvalidArgumentException('Cache connection cannot be empty.');
        }
    }

    public function getKeyPrefix(): string
    {
        return $this->keyPrefix;
    }

    public function getCacheConnection(): string
    {
        return $this->cacheConnection;
    }
}
