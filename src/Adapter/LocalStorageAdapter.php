<?php

declare(strict_types=1);

namespace Haaragard\CircuitBreaker\Adapter;

use Haaragard\CircuitBreaker\Contract\CircuitBreakerInterface;
use Haaragard\CircuitBreaker\Contract\ConfigInterface;
use Illuminate\Support\Carbon;

class LocalStorageAdapter implements CircuitBreakerInterface
{
    private array $container = [];

    public function __construct(private ConfigInterface $config)
    {
    }

    public function isOpen(string $key): bool
    {
        if (! $this->config->isEnabled()) {
            return true;
        }

        $this->reset($key);

        if (! isset($this->container[$key])) {
            return true;
        }
        if ($this->container[$key]['failures'] < $this->config->getFailureThreshold()) {
            return true;
        }

        return false;
    }

    public function recordFailure(string $key): void
    {
        if (! $this->config->isEnabled()) {
            return;
        }

        $this->reset($key);

        if (! isset($this->container[$key])) {
            $this->container[$key] = [
                'failures' => 1,
                'last_failure' => Carbon::now()->toDateTimeImmutable(),
            ];

            return;
        }

        $isLastFailureExpired = $this->isLastFailureExpiredFromCounter($key);
        if ($isLastFailureExpired) {
            $this->forceReset($key);

            $this->container[$key] = [
                'failures' => 1,
                'last_failure' => Carbon::now()->toDateTimeImmutable(),
            ];

            return;
        }

        $this->container[$key]['failures']++;
        $this->container[$key]['last_failure'] = Carbon::now()->toDateTimeImmutable();
    }

    public function recordSuccess(string $key): void
    {
        if (! $this->config->isEnabled()) {
            return;
        }
        if (isset($this->container[$key])) {
            unset($this->container[$key]);
        }
    }

    public function reset(string $key): void
    {
        if (! $this->config->isEnabled()) {
            return;
        }
        if (! isset($this->container[$key])) {
            return;
        }
        if ($this->isLastFailureExpiredForReset($key)) {
            unset($this->container[$key]);
        }
    }

    public function forceReset(string $key): void
    {
        if (! $this->config->isEnabled()) {
            return;
        }
        if (! isset($this->container[$key])) {
            return;
        }

        unset($this->container[$key]);
    }

    private function isLastFailureExpiredFromCounter(string $key): bool
    {
        $lastFailure = Carbon::make($this->container[$key]['last_failure']);
        if (is_null($lastFailure)) {
            return false;
        }
        $lastFailure->addMilliseconds($this->config->getTimeout());

        return Carbon::now()->gt($lastFailure->toDateTimeImmutable());
    }

    private function isLastFailureExpiredForReset(string $key): bool
    {
        $lastFailure = Carbon::make($this->container[$key]['last_failure']);
        if (is_null($lastFailure)) {
            return false;
        }
        $lastFailure->addMilliseconds($this->config->getResetTimeout());

        return Carbon::now()->gt($lastFailure->toDateTimeImmutable());
    }
}
