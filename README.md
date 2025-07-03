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

```php
// Initialize the circuit breaker from the service container
$circuitBreaker = app()->get(CircuitBreakerInterface::class);

// Check if the circuit breaker is open
$isOpen = $circuitBreaker->isOpen(self::class);

// Record a failure
$circuitBreaker->recordFailure(self::class);

// Record a success
$circuitBreaker->recordSuccess(self::class);

// Reset
$circuitBreaker->reset(self::class);

// ForceReset
$circuitBreaker->forceReset(self::class);
```

You can also give any `string` as the first parameter to the methods above, 
which will be used as the circuit breaker name. 
This allows you to have multiple circuit breakers for different purposes.
