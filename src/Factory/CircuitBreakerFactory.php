<?php

declare(strict_types=1);

namespace Haaragard\CircuitBreaker\Factory;

use Haaragard\CircuitBreaker\Contract\CircuitBreakerInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;

class CircuitBreakerFactory
{
    public function __construct(protected Application $app)
    {
    }

    /**
     * @throws InvalidArgumentException|BindingResolutionException
     */
    public function __invoke(): CircuitBreakerInterface
    {
        $service = config('circuit-breaker.service');
        $serviceConfig = config("circuit-breaker.services.{$service}");
        if (is_null($serviceConfig)) {
            throw new InvalidArgumentException("Service configuration for '{$service}' not found.");
        }

        $serviceClass = $serviceConfig['service'];
        if (is_null($serviceClass) || ! class_exists($serviceClass)) {
            throw new InvalidArgumentException("Service class '{$serviceClass}' does not exist.");
        }
        if (! in_array(CircuitBreakerInterface::class, class_implements($serviceClass), true)) {
            throw new InvalidArgumentException("Service class '{$serviceClass}' must implement " . CircuitBreakerInterface::class);
        }

        $isCircuitBreakerEnabled = config('circuit-breaker.enabled', false);

        return $this->app->make($serviceClass, [
            'config' => (new ConfigFactory)(
                $serviceClass,
                $isCircuitBreakerEnabled,
                $serviceConfig
            ),
        ]);
    }
}
