<?php

declare(strict_types=1);

namespace Haaragard\CircuitBreaker\Contract;

interface CircuitBreakerInterface
{
    public function isOpen(string $key): bool;
    public function recordFailure(string $key): void;
    public function recordSuccess(string $key): void;
    public function reset(string $key): void;
}
