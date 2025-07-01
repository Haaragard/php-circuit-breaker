<?php

declare(strict_types=1);

namespace Haaragard\CircuitBreaker\Config;

use InvalidArgumentException;

class Config
{
    public function __construct(
        private bool $enabled,
        private int $timeout,
        private int $failureThreshold,
        private int $resetTimeout,
    ) {
    }

    private function validate(): void
    {
        if ($this->timeout <= 0) {
            throw new InvalidArgumentException('Timeout must be a positive integer.');
        }
        if ($this->failureThreshold <= 0) {
            throw new InvalidArgumentException('Failure threshold must be a positive integer.');
        }
        if ($this->resetTimeout <= 0) {
            throw new InvalidArgumentException('Reset timeout must be a positive integer.');
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getFailureThreshold(): int
    {
        return $this->failureThreshold;
    }

    public function getResetTimeout(): int
    {
        return $this->resetTimeout;
    }
}
