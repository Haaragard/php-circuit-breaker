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

### Configs

Key: `enabled` - (`bool`) If set to `true`, the circuit breaker will be enabled. If set to `false`, it will be disabled.
```php
<?php
// circuit-breaker.php

return [
    'enabled' => true,
];
```

Key: `service` - (`string`) The service to use for the circuit breaker. This can be any **key** service that is listed on `services` **array**.
```php
<?php
// circuit-breaker.php

return [
    'service' => 'default',
];
```

Key: `services` - (`array`) The services that can be used for the circuit breaker. Each service must have a `service` key that points to the class that implements the `CircuitBreakerInterface`.
```php
<?php
// circuit-breaker.php

return [
    'services' => [
        'default' => [
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

Key: `timeout` - (`int`) The timeout in milliseconds for the circuit breaker fail counter. This is the time after which the circuit breaker will be reset the failures before reaching the `failure_threshold`.
```php
<?php
// circuit-breaker.php

return [
    'services' => [
        'default' => [
            'timeout' => 10_000,
        ],
    ],
];
```

Key: `failure_threshold` - (`int`) The number of failures before the circuit breaker is opened. After this threshold is reached, the circuit breaker will be closed and will not allow any requests to pass through until it is reset by the `reset_timeout`.
```php
<?php
// circuit-breaker.php

return [
    'services' => [
        'default' => [
            'failure_threshold' => 5,
        ],
    ],
];
```

Key: `reset_timeout` - (`int`) The time in milliseconds after which the circuit breaker will be reset and will allow requests to pass through again. This is the time after which the circuit breaker will be reset and will allow requests to pass through again.
```php
<?php
// circuit-breaker.php

return [
    'services' => [
        'default' => [
            'reset_timeout' => 30_000,
        ],
    ],
];
```

Key: `key_prefix` - (`string`) The prefix to use for the circuit breaker keys in the storage. This is useful to avoid key collisions in the storage. _(Only in Cache)_
```php
<?php
// circuit-breaker.php

return [
    'services' => [
        'default' => [
            'key_prefix' => 'circuit-breaker:',
        ],
    ],
];
```

Key: `cache_connection` - (`string`) The cache connection to use for the circuit breaker. This can be any cache connection supported by Laravel, plus the custom ones.  _(Only in Cache)_
```php
<?php
// circuit-breaker.php

return [
    'services' => [
        'default' => [
            'cache_connection' => 'default', // Any cache connection supported by Laravel, plus the custom ones.
        ],
    ],
];
```

Key: `service` - (`string`) The service to use for the circuit breaker. This must be a class that implements the `\Haaragard\CircuitBreaker\Contract\CircuitBreakerInterface::class`. _(For custom Drivers only)_
```php
<?php
// circuit-breaker.php

return [
    'services' => [
        'default' => [
            'service' => \Haaragard\CircuitBreaker\Contract\CircuitBreakerInterface::class,
        ],
    ],
];
```

Key: `config` - (`string`) The configuration class to use for the circuit breaker. This is mandatory for custom drivers and must implement the `\Haaragard\CircuitBreaker\Contract\ConfigInterface::class`. _(For custom Drivers only)_
```php
<?php
// circuit-breaker.php

return [
    'services' => [
        'default' => [
            'config' => \Haaragard\CircuitBreaker\Contract\ConfigInterface::class,
        ],
    ],
];
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

### Custom Circuit Breaker Drivers

If you need a custom circuit breaker driver, you can create one by implementing the `CircuitBreakerInterface` interface.

```php
<?php
// circuit-breaker.php

return [
    'enabled' => true,
    'service' => 'my_own_driver',
    'services' => [
        'my_own_driver' => [
            // You can define any configuration you need for your custom driver here.
            // ...

            'service' => \PathToMyOwnDriver\MyOwnDriver::class, // Mandatory. Needs to implement the `CircuitBreakerInterface`
            'config' => \PathToMyOwnDriverConfig\MyOwnDriverConfig::class, // Mandatory.
        ],
    ],
];
```
