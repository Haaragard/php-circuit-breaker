<?php

declare(strict_types=1);

namespace Haaragard\Test\Unit\Adapter;

use Haaragard\CircuitBreaker\Adapter\CacheStorageAdapter;
use Haaragard\CircuitBreaker\Contract\CacheConfigInterface;
use Haaragard\Test\Unit\UnitTestCase;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

class CacheStorageAdapterTest extends UnitTestCase
{
    private CacheConfigInterface|MockObject $configMock;

    private Repository|MockObject $cacheRepositoryMock;

    private CacheStorageAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configMock = $this->createMock(CacheConfigInterface::class);
        $this->cacheRepositoryMock = $this->createMock(Repository::class);

        // Mock the Cache facade to return our mock repository
        Cache::shouldReceive('store')
            ->andReturn($this->cacheRepositoryMock);

        $this->adapter = new CacheStorageAdapter($this->configMock);
    }

    #[Test]
    #[DataProvider('shouldEndMethodEarlyWhenFeatureIsInactiveDataProvider')]
    public function shouldEndMethodEarlyWhenFeatureIsInactive(string $method, ?bool $return): void
    {
        $this->configMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->cacheRepositoryMock->expects($this->never())
            ->method('get');
        $this->cacheRepositoryMock->expects($this->never())
            ->method('set');

        $result = $this->adapter->{$method}('fake-key');

        $this->assertEquals($return, $result);
    }

    public static function shouldEndMethodEarlyWhenFeatureIsInactiveDataProvider(): array
    {
        return [
            'Run isOpen method' => ['method' => 'isOpen', 'return' => true],
            'Run recordFailure method' => ['method' => 'recordFailure', 'return' => null],
            'Run recordSuccess method' => ['method' => 'recordSuccess', 'return' => null],
            'Run reset method' => ['method' => 'reset', 'return' => null],
            'Run forceReset method' => ['method' => 'forceReset', 'return' => null],
        ];
    }

    #[Test]
    #[DataProvider('shouldRunIsOpenWithAllCasesDataProvider')]
    public function shouldRunIsOpenWithAllCases(
        string $timeSetForTest,
        array $cacheRepositoryMap,
        bool $shouldReset,
        bool $expectedResult
    ): void {
        $this->markTestSkipped('temporary skip, needs fix. (Problem with cache instance maybe?)');

        Carbon::setTestNow(Carbon::make($timeSetForTest));

        $this->configMock->method('isEnabled')->willReturn(true);
        $this->configMock->method('getTimeout')->willReturn(5_000);
        $this->configMock->method('getFailureThreshold')->willReturn(5);
        $this->configMock->method('getResetTimeout')->willReturn(10_000);
        $this->configMock->method('getKeyPrefix')->willReturn('fake-prefix-key');
        $this->configMock->method('getCacheConnection')->willReturn('fake-connection');

        $this->cacheRepositoryMock->method('get')
            ->willReturnOnConsecutiveCalls(...$cacheRepositoryMap);

        if ($shouldReset) {
            $this->cacheRepositoryMock->expects($this->exactly(2))
                ->method('forget')
                ->with($this->callback(function ($key): bool {
                    return in_array($key, [
                        'fake-prefix-key:fake-key:failures',
                        'fake-prefix-key:fake-key:last_failure',
                    ], true);
                }));
        } else {
            $this->cacheRepositoryMock->expects($this->never())
                ->method('forget');
        }

        $result = $this->adapter->isOpen('fake-key');

        $this->assertEquals($expectedResult, $result);
    }

    public static function shouldRunIsOpenWithAllCasesDataProvider(): array
    {
        $timeSetForTest = '2025-01-01 00:00:00';

        return [
            'Run isOpen with no failures' => [
                'timeSetForTest' => $timeSetForTest,
                'cacheRepositoryMap' => [
                    0,
                    0,
                ],
                'shouldReset' => false,
                'expectedResult' => true,
            ],
            'Run isOpen with low failures but not on threshold' => [
                'timeSetForTest' => $timeSetForTest,
                'cacheRepositoryMap' => [
                    Carbon::make($timeSetForTest)->timestamp,
                    1,
                ],
                'shouldReset' => false,
                'expectedResult' => true,
            ],
            'Run isOpen with failures same as threshold' => [
                'timeSetForTest' => $timeSetForTest,
                'cacheRepositoryMap' => [
                    Carbon::make($timeSetForTest)->timestamp,
                    5,
                    Carbon::make($timeSetForTest)->timestamp,
                ],
                'shouldReset' => false,
                'expectedResult' => false,
            ],
            'Run isOpen with failures greater than threshold' => [
                'timeSetForTest' => $timeSetForTest,
                'cacheRepositoryMap' => [
                    Carbon::make($timeSetForTest)->timestamp,
                    6,
                    Carbon::make($timeSetForTest)->timestamp,
                ],
                'shouldReset' => false,
                'expectedResult' => false,
            ],
            'Run isOpen with failures greater than threshold ready for reset' => [
                'timeSetForTest' => $timeSetForTest,
                'cacheRepositoryMap' => [
                    Carbon::make($timeSetForTest)->subMilliseconds(10_001)->timestamp,
                    6,
                    0,
                ],
                'shouldReset' => true,
                'expectedResult' => true,
            ],
        ];
    }
}
