<?php

declare(strict_types=1);

namespace Haaragard\Test\Unit\Adapter;

use Haaragard\CircuitBreaker\Adapter\LocalStorageAdapter;
use Haaragard\CircuitBreaker\Contract\CircuitBreakerInterface;
use Haaragard\CircuitBreaker\Contract\ConfigInterface;
use Haaragard\Test\Unit\UnitTestCase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

class LocalStorageAdapterTest extends UnitTestCase
{
    private LocalStorageAdapter $adapter;

    private ConfigInterface|MockObject $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(ConfigInterface::class);
        $this->adapter = new LocalStorageAdapter($this->config);
    }

    #[Test]
    public function implementsCircuitBreakerInterface(): void
    {
        $this->assertInstanceOf(CircuitBreakerInterface::class, $this->adapter);
    }

    public static function disabledConfigProvider(): array
    {
        return [
            'isOpen when disabled' => ['isOpen', 'test-key'],
            'recordFailure when disabled' => ['recordFailure', 'test-key'],
            'recordSuccess when disabled' => ['recordSuccess', 'test-key'],
            'reset when disabled' => ['reset', 'test-key'],
            'forceReset when disabled' => ['forceReset', 'test-key'],
        ];
    }

    #[Test]
    #[DataProvider('disabledConfigProvider')]
    public function methodsHandleDisabledConfigCorrectly(string $method, string $key): void
    {
        $this->config->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $result = $this->adapter->$method($key);

        if ($method === 'isOpen') {
            $this->assertTrue($result);
        } else {
            // For void methods, just verify no exceptions are thrown
            $this->assertTrue(true);
        }
    }

    public static function noFailuresRecordedProvider(): array
    {
        return [
            'isOpen with no failures' => ['isOpen', true],
            'recordSuccess with no failures' => ['recordSuccess', null],
            'reset with no failures' => ['reset', null],
            'forceReset with no failures' => ['forceReset', null],
        ];
    }

    #[Test]
    #[DataProvider('noFailuresRecordedProvider')]
    public function methodsHandleNoFailuresRecordedCorrectly(string $method, ?bool $expectedResult): void
    {
        $this->config->method('isEnabled')->willReturn(true);

        // For reset methods, we need to mock the getResetTimeout method
        if ($method === 'reset') {
            $this->config->method('getResetTimeout')->willReturn(5000);
        }

        $result = $this->adapter->$method('test-key');

        if ($expectedResult !== null) {
            $this->assertEquals($expectedResult, $result);
        } else {
            // For void methods, just verify no exceptions are thrown
            $this->assertTrue(true);
        }
    }

    public static function thresholdTestProvider(): array
    {
        return [
            'failures below threshold' => [2, 5, true],   // Circuit should remain OPEN
            'failures reach threshold' => [3, 3, false],  // Circuit should be CLOSED
            'failures exceed threshold' => [5, 3, false], // Circuit should be CLOSED
        ];
    }

    #[Test]
    #[DataProvider('thresholdTestProvider')]
    public function isOpenBehavesCorrectlyBasedOnThreshold(int $failureCount, int $threshold, bool $expectedResult): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getFailureThreshold')->willReturn($threshold);
        $this->config->method('getTimeout')->willReturn(1000);
        $this->config->method('getResetTimeout')->willReturn(5000);

        // Record failures
        for ($i = 0; $i < $failureCount; $i++) {
            $this->adapter->recordFailure('test-key');
        }

        $result = $this->adapter->isOpen('test-key');

        $this->assertEquals($expectedResult, $result);
    }

    #[Test]
    public function recordFailureIncrementsFailureCount(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getFailureThreshold')->willReturn(5);
        $this->config->method('getTimeout')->willReturn(1000);
        $this->config->method('getResetTimeout')->willReturn(5000);

        $this->adapter->recordFailure('test-key');
        $this->adapter->recordFailure('test-key');

        // Verify by checking isOpen behavior
        $result = $this->adapter->isOpen('test-key');
        $this->assertTrue($result); // Still below threshold
    }

    #[Test]
    public function recordFailureResetsCounterWhenLastFailureExpired(): void
    {
        Carbon::setTestNow('2023-01-01 12:00:00');

        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getFailureThreshold')->willReturn(3);
        $this->config->method('getTimeout')->willReturn(1000);
        $this->config->method('getResetTimeout')->willReturn(5000);

        // Record initial failures
        $this->adapter->recordFailure('test-key');
        $this->adapter->recordFailure('test-key');

        // Move time forward beyond timeout
        Carbon::setTestNow('2023-01-01 12:00:02');

        // Record another failure - should reset counter
        $this->adapter->recordFailure('test-key');

        $result = $this->adapter->isOpen('test-key');
        $this->assertTrue($result); // Should be open since counter was reset

        Carbon::setTestNow();
    }

    #[Test]
    public function recordSuccessRemovesFailureRecord(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getFailureThreshold')->willReturn(3);
        $this->config->method('getResetTimeout')->willReturn(5000);

        // Record failures
        $this->adapter->recordFailure('test-key');
        $this->adapter->recordFailure('test-key');

        // Record success - should clear failures
        $this->adapter->recordSuccess('test-key');

        $result = $this->adapter->isOpen('test-key');
        $this->assertTrue($result); // Should be open (no failures recorded)
    }

    #[Test]
    public function resetClearsFailuresWhenResetTimeoutExpired(): void
    {
        Carbon::setTestNow('2023-01-01 12:00:00');

        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getFailureThreshold')->willReturn(2);
        $this->config->method('getResetTimeout')->willReturn(5000);

        // Record failures to reach threshold
        $this->adapter->recordFailure('test-key');
        $this->adapter->recordFailure('test-key');

        // Verify circuit is closed
        $this->assertFalse($this->adapter->isOpen('test-key'));

        // Move time forward beyond reset timeout
        Carbon::setTestNow('2023-01-01 12:00:06');

        // Call reset manually
        $this->adapter->reset('test-key');

        $result = $this->adapter->isOpen('test-key');
        $this->assertTrue($result); // Should be open after reset

        Carbon::setTestNow();
    }

    public static function forceResetProvider(): array
    {
        return [
            'force reset immediately clears failures' => [2, 2],
            'force reset with exceeded threshold' => [5, 3],
        ];
    }

    #[Test]
    #[DataProvider('forceResetProvider')]
    public function forceResetClearsFailuresImmediately(int $failureCount, int $threshold): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getFailureThreshold')->willReturn($threshold);
        $this->config->method('getTimeout')->willReturn(1000);
        $this->config->method('getResetTimeout')->willReturn(5000);

        // Record failures to reach/exceed threshold
        for ($i = 0; $i < $failureCount; $i++) {
            $this->adapter->recordFailure('test-key');
        }

        // Verify circuit is closed
        $this->assertFalse($this->adapter->isOpen('test-key'));

        // Force reset
        $this->adapter->forceReset('test-key');

        $result = $this->adapter->isOpen('test-key');
        $this->assertTrue($result); // Should be open after force reset
    }

    public static function independentKeysProvider(): array
    {
        return [
            'two keys with different failure counts' => ['key1', 'key2', 2, 1, 2],
            'multiple keys isolation' => ['service-a', 'service-b', 3, 2, 3],
        ];
    }

    #[Test]
    #[DataProvider('independentKeysProvider')]
    public function differentKeysAreHandledIndependently(
        string $key1,
        string $key2,
        int $key1Failures,
        int $key2Failures,
        int $threshold
    ): void {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getFailureThreshold')->willReturn($threshold);
        $this->config->method('getTimeout')->willReturn(1000);
        $this->config->method('getResetTimeout')->willReturn(5000);

        // Record failures for first key
        for ($i = 0; $i < $key1Failures; $i++) {
            $this->adapter->recordFailure($key1);
        }

        // Record failures for second key
        for ($i = 0; $i < $key2Failures; $i++) {
            $this->adapter->recordFailure($key2);
        }

        // Check if circuit is open/closed based on threshold
        // If failures >= threshold, circuit should be CLOSED (false)
        // If failures < threshold, circuit should be OPEN (true)
        $key1ShouldBeOpen = $key1Failures < $threshold;
        $key2ShouldBeOpen = $key2Failures < $threshold;

        $this->assertEquals($key1ShouldBeOpen, $this->adapter->isOpen($key1));
        $this->assertEquals($key2ShouldBeOpen, $this->adapter->isOpen($key2));
    }

    #[Test]
    public function completeCircuitBreakerFlow(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getFailureThreshold')->willReturn(3);
        $this->config->method('getTimeout')->willReturn(1000);
        $this->config->method('getResetTimeout')->willReturn(5000);

        // 1. Initially open (no failures)
        $this->assertTrue($this->adapter->isOpen('test-key'));

        // 2. Record failures below threshold
        $this->adapter->recordFailure('test-key');
        $this->adapter->recordFailure('test-key');
        $this->assertTrue($this->adapter->isOpen('test-key'));

        // 3. Reach threshold - circuit closes
        $this->adapter->recordFailure('test-key');
        $this->assertFalse($this->adapter->isOpen('test-key'));

        // 4. Success opens circuit again
        $this->adapter->recordSuccess('test-key');
        $this->assertTrue($this->adapter->isOpen('test-key'));
    }

    #[Test]
    public function timeoutBehaviorWithCarbonMocking(): void
    {
        Carbon::setTestNow('2023-01-01 12:00:00');

        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getFailureThreshold')->willReturn(2);
        $this->config->method('getTimeout')->willReturn(2000);
        $this->config->method('getResetTimeout')->willReturn(5000);

        // Record failures
        $this->adapter->recordFailure('test-key');

        // Move time forward but not beyond timeout
        Carbon::setTestNow('2023-01-01 12:00:01');
        $this->adapter->recordFailure('test-key');

        // Circuit should be closed
        $this->assertFalse($this->adapter->isOpen('test-key'));

        // Move time beyond timeout
        Carbon::setTestNow('2023-01-01 12:00:04');

        // Next failure should reset counter due to timeout
        $this->adapter->recordFailure('test-key');

        // Should still be open since counter was reset
        $this->assertTrue($this->adapter->isOpen('test-key'));

        Carbon::setTestNow();
    }
}
