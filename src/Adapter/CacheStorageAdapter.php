<?php

declare(strict_types=1);

namespace Haaragard\CircuitBreaker\Adapter;

use Haaragard\CircuitBreaker\Contract\CacheConfigInterface;
use Haaragard\CircuitBreaker\Contract\CircuitBreakerInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class CacheStorageAdapter implements CircuitBreakerInterface
{
    protected const CACHE_KEY_SUFFIX_FAILURES = ':failures';

    protected const CACHE_KEY_SUFFIX_LAST_FAILURE = ':last_failure';

    protected Repository $cacheRepository;

    public function __construct(
        private CacheConfigInterface $config,
    ) {
        $this->cacheRepository = $this->resolveCache();
    }

    public function isOpen(string $key): bool
    {
        if (! $this->config->isEnabled()) {
            return true;
        }

        $this->reset($key);

        $redisFailuresKey = $this->resolveFailuresKey($key);
        $failures = $this->cacheRepository->get($redisFailuresKey, 0);
        if ($failures < $this->config->getFailureThreshold()) {
            return true;
        }

        $lastFailureKey = $this->resolveLastFailureKey($key);
        // Should be a timestamp
        $lastFailure = $this->cacheRepository->get($lastFailureKey);
        if (is_null($lastFailure)) {
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

        $failuresKey = $this->resolveFailuresKey($key);
        $failures = $this->cacheRepository->get($failuresKey, 0);

        $expireDate = Carbon::now()->addMilliseconds($this->config->getTimeout());
        $ttl = Carbon::now()->diffAsDateInterval(date: $expireDate, absolute: true);

        $isLastFailureExpired = fn (string $key): bool => $this->isLastFailureExpiredFromCounter($key);
        if ($failures > 0 && $isLastFailureExpired($key)) {
            $failures = 0;
        }

        $this->cacheRepository->set(
            $failuresKey,
            $failures + 1,
            $ttl,
        );
        $this->cacheRepository->set(
            $this->resolveLastFailureKey($key),
            Carbon::now()->timestamp,
            $ttl,
        );
    }

    public function recordSuccess(string $key): void
    {
        if (! $this->config->isEnabled()) {
            return;
        }

        $this->forceReset($key);
    }

    public function reset(string $key): void
    {
        if (! $this->config->isEnabled()) {
            return;
        }
        if ($this->isLastFailureExpiredForReset($key)) {
            $failuresKey = $this->resolveFailuresKey($key);
            $this->cacheRepository->forget($failuresKey);

            $lastFailureKey = $this->resolveLastFailureKey($key);
            $this->cacheRepository->forget($lastFailureKey);
        }
    }

    public function forceReset(string $key): void
    {
        if (! $this->config->isEnabled()) {
            return;
        }

        $failuresKey = $this->resolveFailuresKey($key);
        $this->cacheRepository->forget($failuresKey);

        $lastFailureKey = $this->resolveLastFailureKey($key);
        $this->cacheRepository->forget($lastFailureKey);
    }

    private function isLastFailureExpiredFromCounter(string $key): bool
    {
        $lastFailureKey = $this->resolveKey($key) . self::CACHE_KEY_SUFFIX_LAST_FAILURE;
        $lastFailureTimestamp = $this->cacheRepository->get($lastFailureKey, 0);
        $lastFailure = Carbon::createFromTimestamp($lastFailureTimestamp);
        if (is_null($lastFailure)) {
            return false;
        }
        $lastFailure->addMilliseconds($this->config->getTimeout());

        return Carbon::now()->gt($lastFailure->toDateTimeImmutable());
    }

    private function isLastFailureExpiredForReset(string $key): bool
    {
        $lastFailureKey = $this->resolveLastFailureKey($key);
        $lastFailureTimestamp = $this->cacheRepository->get($lastFailureKey, 0);
        if ($lastFailureTimestamp === 0) {
            return false;
        }

        $lastFailure = Carbon::createFromTimestamp($lastFailureTimestamp);
        $lastFailure->addMilliseconds($this->config->getResetTimeout());

        return Carbon::now()->gt($lastFailure->toDateTimeImmutable());
    }

    private function resolveKey(string $key): string
    {
        return $this->config->getKeyPrefix() . ":{$key}";
    }

    private function resolveFailuresKey(string $key): string
    {
        return $this->resolveKey($key) . self::CACHE_KEY_SUFFIX_FAILURES;
    }

    private function resolveLastFailureKey(string $key): string
    {
        return $this->resolveKey($key) . self::CACHE_KEY_SUFFIX_LAST_FAILURE;
    }

    private function resolveCache(): Repository
    {
        return Cache::store($this->config->getCacheConnection());
    }
}
