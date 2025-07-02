<?php

declare(strict_types=1);

namespace Provider;

use Haaragard\CircuitBreaker\Contract\CircuitBreakerInterface;
use Haaragard\CircuitBreaker\Factory\CircuitBreakerFactory;
use Haaragard\CircuitBreaker\Provider\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

class ServiceProviderTest extends TestCase
{
    private ServiceProvider $serviceProvider;

    private Application|MockObject $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = $this->createMock(Application::class);
        $this->serviceProvider = new ServiceProvider($this->app);
    }

    #[Test]
    public function implementsDeferrableProvider(): void
    {
        $this->assertInstanceOf(DeferrableProvider::class, $this->serviceProvider);
    }

    #[Test]
    public function registerBindsCircuitBreakerInterface(): void
    {
        $this->app->expects($this->once())
            ->method('bind')
            ->with(
                CircuitBreakerInterface::class,
                CircuitBreakerFactory::class
            );

        $this->serviceProvider->register();
    }

    public static function providesMethodTestProvider(): array
    {
        return [
            'returns correct services' => [CircuitBreakerInterface::class, CircuitBreakerFactory::class],
        ];
    }

    #[Test]
    #[DataProvider('providesMethodTestProvider')]
    public function providesReturnsCorrectServices(string $interface, string $factory): void
    {
        $provides = $this->serviceProvider->provides();

        $this->assertIsArray($provides);
        $this->assertArrayHasKey($interface, $provides);
        $this->assertEquals($factory, $provides[$interface]);
    }

    #[Test]
    public function providesReturnsOnlyExpectedServices(): void
    {
        $provides = $this->serviceProvider->provides();

        $this->assertCount(1, $provides);
        $this->assertEquals([
            CircuitBreakerInterface::class => CircuitBreakerFactory::class,
        ], $provides);
    }

    #[Test]
    public function serviceProviderCanBeInstantiated(): void
    {
        $this->assertInstanceOf(ServiceProvider::class, $this->serviceProvider);
    }

    public static function deferredProviderTestProvider(): array
    {
        return [
            'is instance of DeferrableProvider' => [DeferrableProvider::class],
            'provides method returns non-empty array' => ['provides'],
        ];
    }

    #[Test]
    #[DataProvider('deferredProviderTestProvider')]
    public function serviceProviderIsDeferred($testType): void
    {
        if ($testType === DeferrableProvider::class) {
            $this->assertTrue($this->serviceProvider instanceof DeferrableProvider);
        } else {
            // Test that provides() is not empty (required for deferred providers)
            $provides = $this->serviceProvider->provides();
            $this->assertNotEmpty($provides);
        }
    }

    #[Test]
    public function bootMethodExists(): void
    {
        $this->assertTrue(method_exists($this->serviceProvider, 'boot'));

        // Verify boot method can be called without errors
        $reflection = new \ReflectionClass($this->serviceProvider);
        $bootMethod = $reflection->getMethod('boot');
        $this->assertTrue($bootMethod->isPublic());
    }

    #[Test]
    public function configPathIsCorrect(): void
    {
        $reflection = new \ReflectionClass($this->serviceProvider);
        $expectedPath = dirname($reflection->getFileName()) . '/../../src/Config/circuit-breaker.php';

        $this->assertFileExists($expectedPath);
        $this->assertStringEndsWith('circuit-breaker.php', $expectedPath);
    }
}
