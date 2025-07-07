# Circuit Breaker

## Installation

```bash
composer require haaragard/circuit-breaker
```

### Publish config file after installation:

_(optional)_ Only needed for customizing the default configuration. 
(like using cache or creating new drivers).

```bash
php artisan vendor:publish --tag=haaragard-circuit-breaker-config
```

## Supported laravel versions

- 10.x
- 11.x
- 12.x

## How it works

### Plug-and-play

Using right after the installation is possible, as the package comes with a default configuration that uses the `LocalStorageAdapter` driver.
This driver stores the circuit breaker state in-memory and is suitable for local development or testing purposes.

```php
// Initialize the circuit breaker from the service container
$circuitBreaker = app()->get(CircuitBreakerInterface::class);

// Check if the circuit breaker is open
$isOpen = $circuitBreaker->isOpen('string-key');

// Record a failure
$circuitBreaker->recordFailure('string-key');

// Record a success
$circuitBreaker->recordSuccess('string-key');

// Reset
$circuitBreaker->reset('string-key');

// ForceReset
$circuitBreaker->forceReset('string-key');
```

You can also give any `string` as the first parameter to the methods above,
which will be used as the circuit breaker name.
This allows you to have multiple circuit breakers for different purposes.

### Changing the default config

You can change the default configuration by publishing the config file and modifying it to your needs.

```bash
php artisan vendor:publish --tag=haaragard-circuit-breaker-config
```

### Laravel Custom Cache Drivers

You can use any cache driver supported by Laravel by changing the `driver` key in the configuration file.
The CacheStorageAdapter supports all laravel cache drivers, including your custom ones as long as they implement
and works as expected with the Laravel Cache facade.

```php
<?php

// circuit-breaker.php

return [
    'enabled' => true,
    'service' => 'cache',
    'services' => [
        'cache' => [
            'timeout' => 10_000,
            'failure_threshold' => 5,
            'reset_timeout' => 30_000,
            'key_prefix' => 'circuit-breaker:',
            'cache_connection' => 'default', // Any cache connection supported by Laravel, plus the custom ones.
            'service' => \Haaragard\CircuitBreaker\Adapter\CacheStorageAdapter::class,
        ],
    ],
];
```
