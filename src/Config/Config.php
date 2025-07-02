<?php

declare(strict_types=1);

namespace Haaragard\CircuitBreaker\Config;

use Haaragard\CircuitBreaker\Contract\ConfigInterface;
use InvalidArgumentException;

class Config implements ConfigInterface
{
    public function __construct(
        protected bool $enabled,
        protected int $timeout,
        protected int $failureThreshold,
        protected int $resetTimeout,
    ) {
        $this->validate();
    }

    protected function validate(): void
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
