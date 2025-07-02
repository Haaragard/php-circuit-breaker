<?php

declare(strict_types=1);

namespace Haaragard\Test\Unit\Config;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Haaragard\CircuitBreaker\Config\Config;

class ConfigTest extends TestCase
{
    #[Test]
    public function constructorWithValidParameters(): void
    {
        $config = new Config(
            enabled: true,
            timeout: 5000,
            failureThreshold: 3,
            resetTimeout: 60000
        );

        $this->assertTrue($config->isEnabled());
        $this->assertEquals(5000, $config->getTimeout());
        $this->assertEquals(3, $config->getFailureThreshold());
        $this->assertEquals(60000, $config->getResetTimeout());
    }

    #[Test]
    public function constructorWithDisabledConfig(): void
    {
        $config = new Config(
            enabled: false,
            timeout: 1000,
            failureThreshold: 5,
            resetTimeout: 30000
        );

        $this->assertFalse($config->isEnabled());
        $this->assertEquals(1000, $config->getTimeout());
        $this->assertEquals(5, $config->getFailureThreshold());
        $this->assertEquals(30000, $config->getResetTimeout());
    }

    #[Test]
    #[DataProvider('invalidParametersProvider')]
    public function constructorThrowsExceptionWithInvalidParameters(
        bool $enabled,
        int $timeout,
        int $failureThreshold,
        int $resetTimeout,
        string $expectedMessage
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new Config(
            enabled: $enabled,
            timeout: $timeout,
            failureThreshold: $failureThreshold,
            resetTimeout: $resetTimeout
        );
    }

    public static function invalidParametersProvider(): array
    {
        return [
            'timeout is zero' => [
                'enabled' => true,
                'timeout' => 0,
                'failureThreshold' => 3,
                'resetTimeout' => 60000,
                'expectedMessage' => 'Timeout must be a positive integer.',
            ],
            'timeout is negative' => [
                'enabled' => true,
                'timeout' => -1000,
                'failureThreshold' => 3,
                'resetTimeout' => 60000,
                'expectedMessage' => 'Timeout must be a positive integer.',
            ],
            'failure threshold is zero' => [
                'enabled' => true,
                'timeout' => 5000,
                'failureThreshold' => 0,
                'resetTimeout' => 60000,
                'expectedMessage' => 'Failure threshold must be a positive integer.',
            ],
            'failure threshold is negative' => [
                'enabled' => true,
                'timeout' => 5000,
                'failureThreshold' => -3,
                'resetTimeout' => 60000,
                'expectedMessage' => 'Failure threshold must be a positive integer.',
            ],
            'reset timeout is zero' => [
                'enabled' => true,
                'timeout' => 5000,
                'failureThreshold' => 3,
                'resetTimeout' => 0,
                'expectedMessage' => 'Reset timeout must be a positive integer.',
            ],
            'reset timeout is negative' => [
                'enabled' => true,
                'timeout' => 5000,
                'failureThreshold' => 3,
                'resetTimeout' => -60000,
                'expectedMessage' => 'Reset timeout must be a positive integer.',
            ],
            'multiple invalid - timeout and failure threshold zero' => [
                'enabled' => true,
                'timeout' => 0,
                'failureThreshold' => 0,
                'resetTimeout' => 60000,
                'expectedMessage' => 'Timeout must be a positive integer.',
            ],
            'multiple invalid - all parameters zero' => [
                'enabled' => true,
                'timeout' => 0,
                'failureThreshold' => 0,
                'resetTimeout' => 0,
                'expectedMessage' => 'Timeout must be a positive integer.',
            ],
            'multiple invalid - timeout and reset timeout negative' => [
                'enabled' => false,
                'timeout' => -1,
                'failureThreshold' => 5,
                'resetTimeout' => -1,
                'expectedMessage' => 'Timeout must be a positive integer.',
            ],
        ];
    }

    #[Test]
    #[DataProvider('validParametersProvider')]
    public function constructorWithVariousValidParameters(
        bool $enabled,
        int $timeout,
        int $failureThreshold,
        int $resetTimeout
    ): void {
        $config = new Config(
            enabled: $enabled,
            timeout: $timeout,
            failureThreshold: $failureThreshold,
            resetTimeout: $resetTimeout
        );

        $this->assertEquals($enabled, $config->isEnabled());
        $this->assertEquals($timeout, $config->getTimeout());
        $this->assertEquals($failureThreshold, $config->getFailureThreshold());
        $this->assertEquals($resetTimeout, $config->getResetTimeout());
    }

    public static function validParametersProvider(): array
    {
        return [
            'minimum valid values' => [
                'enabled' => true,
                'timeout' => 1,
                'failureThreshold' => 1,
                'resetTimeout' => 1,
            ],
            'large values disabled' => [
                'enabled' => false,
                'timeout' => 999999,
                'failureThreshold' => 100,
                'resetTimeout' => 999999,
            ],
            'typical production values' => [
                'enabled' => true,
                'timeout' => 5000,
                'failureThreshold' => 5,
                'resetTimeout' => 120000,
            ],
            'high threshold values' => [
                'enabled' => false,
                'timeout' => 10000,
                'failureThreshold' => 10,
                'resetTimeout' => 300000,
            ],
        ];
    }

    #[Test]
    #[DataProvider('enabledStateProvider')]
    public function isEnabledReturnsBooleanValue(bool $enabled): void
    {
        $config = new Config($enabled, 1000, 3, 30000);

        $this->assertIsBool($config->isEnabled());
        $this->assertEquals($enabled, $config->isEnabled());
    }

    public static function enabledStateProvider(): array
    {
        return [
            'enabled' => [true],
            'disabled' => [false],
        ];
    }

    #[Test]
    public function gettersReturnCorrectTypes(): void
    {
        $config = new Config(true, 5000, 3, 60000);

        $this->assertIsInt($config->getTimeout());
        $this->assertIsInt($config->getFailureThreshold());
        $this->assertIsInt($config->getResetTimeout());
    }
}
