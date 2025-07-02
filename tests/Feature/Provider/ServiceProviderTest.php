<?php

declare(strict_types=1);

namespace Haaragard\Test\Feature\Provider;

use Haaragard\CircuitBreaker\Adapter\LocalStorageAdapter;
use Haaragard\CircuitBreaker\Contract\CircuitBreakerInterface;
use Haaragard\Test\Feature\FeatureTestCase;
use Orchestra\Testbench\Concerns\WithWorkbench;
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
    public function shouldAppProvideInstanceCorrectly(): void
    {
        $expectedClass = LocalStorageAdapter::class;
        $classInstance = $this->app->get(CircuitBreakerInterface::class);

        $this->assertInstanceOf($expectedClass, $classInstance);
    }
}
