<?php

declare(strict_types=1);

namespace Haaragard\CircuitBreaker\Adapter;

use Haaragard\CircuitBreaker\Config\Config;
use Haaragard\CircuitBreaker\Contract\CircuitBreakerInterface;
use Illuminate\Support\Carbon;

class LocalStorageAdapter implements CircuitBreakerInterface
{
    private array $container = [];

    public function __construct(private Config $config)
    {
    }

    public function isOpen(string $key): bool
    {
        if (! $this->config->isEnabled()) {
            return true;
        }

        if (! isset($this->container[$key])) {
            return true;
        }

        $register = $this->container[$key];
        if ($register['failures'] >= $this->config->getFailureThreshold()) {
            $timeFromLastFailure = Carbon::now()->diffInMilliseconds($register['last_failure']);
            if ($timeFromLastFailure <= $this->config->getFailureThreshold()) {
                return false;
            }
        }

        return true;
    }

    public function recordFailure(string $key): void
    {
        if (! $this->config->isEnabled()) {
            return;
        }

        if (! isset($this->container[$key])) {
            $this->container[$key] = [
                'failures' => 1,
                'last_failure' => Carbon::now()->toDateTimeImmutable(),
            ];

            return;
        }

        $timeFromLastFailure = Carbon::now()->diffInMilliseconds($this->container[$key]['last_failure']);
        if ($timeFromLastFailure > $this->config->getResetTimeout()) {
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
        $this->reset($key);
    }

    public function reset(string $key): void
    {
        if (! $this->config->isEnabled()) {
            return;
        }

        if (isset($this->container[$key])) {
            unset($this->container[$key]);
        }
    }
}
