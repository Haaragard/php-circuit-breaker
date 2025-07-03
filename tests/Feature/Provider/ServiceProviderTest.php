<?php

declare(strict_types=1);

namespace Haaragard\Test\Feature\Provider;

use Closure;
use Haaragard\CircuitBreaker\Adapter\CacheStorageAdapter;
use Haaragard\CircuitBreaker\Adapter\LocalStorageAdapter;
use Haaragard\CircuitBreaker\Contract\CircuitBreakerInterface;
use Haaragard\Test\Feature\FeatureTestCase;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ServiceProviderTest extends FeatureTestCase
{
    #[Test]
    public function shouldMergeConfig(): void
    {
        $config = require __DIR__.'/../../../src/Config/circuit-breaker.php';
        $providedConfig = config('circuit-breaker');

        $this->assertEquals($config, $providedConfig);
    }

    #[Test]
    #[DataProvider('shouldAppProvideInstanceCorrectlyDataProvider')]
    public function shouldAppProvideInstanceCorrectly(
        array $config,
        Closure $morePrepares,
        string $expectedClass
    ): void {
        config()->set('circuit-breaker', $config);

        $morePrepares();

        $classInstance = $this->app->get(CircuitBreakerInterface::class);

        $this->assertInstanceOf($expectedClass, $classInstance);
    }

    public static function shouldAppProvideInstanceCorrectlyDataProvider(): array
    {
        return [
            'instance of LocalStorageAdapter by default' => [
                'config' => [
                    'enabled' => true,
                    'service' => 'local-storage',
                    'services' => [
                        'local-storage' => [
                            'timeout' => 1_000,
                            'failure_threshold' => 5,
                            'reset_timeout' => 5_000,
                        ],
                    ],
                ],
                'morePrepares' => fn () => null,
                'expectedClass' => LocalStorageAdapter::class,
            ],
            'instance of LocalStorageAdapter when specified' => [
                'config' => [
                    'enabled' => true,
                    'service' => 'local-storage',
                    'services' => [
                        'local-storage' => [
                            'timeout' => 1_000,
                            'failure_threshold' => 5,
                            'reset_timeout' => 5_000,
                            'service' => LocalStorageAdapter::class,
                        ],
                    ],
                ],
                'morePrepares' => fn () => null,
                'expectedClass' => LocalStorageAdapter::class,
            ],
            'instance of CacheStorageAdapter' => [
                'config' => [
                    'enabled' => true,
                    'service' => 'cache-storage',
                    'services' => [
                        'cache-storage' => [
                            'timeout' => 1_000,
                            'failure_threshold' => 5,
                            'reset_timeout' => 5_000,
                            'key_prefix' => 'circuit-breaker:',
                            'cache_connection' => 'default',
                            'service' => CacheStorageAdapter::class,
                        ],
                    ],
                ],
                'morePrepares' => fn () => Cache::shouldReceive('store')
                    ->once()
                    ->andReturn(Mockery::mock(Repository::class)),
                'expectedClass' => CacheStorageAdapter::class,
            ],
        ];
    }
}
