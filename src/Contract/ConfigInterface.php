<?php

declare(strict_types=1);

namespace Haaragard\CircuitBreaker\Contract;

interface ConfigInterface
{
    public function isEnabled(): bool;
    public function getTimeout(): int;
    public function getFailureThreshold(): int;
    public function getResetTimeout(): int;
}
