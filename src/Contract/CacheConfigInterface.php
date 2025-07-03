<?php

declare(strict_types=1);

namespace Haaragard\CircuitBreaker\Contract;

interface CacheConfigInterface extends ConfigInterface
{
    public function getKeyPrefix(): string;
    public function getCacheConnection(): string;
}
