<?php

declare(strict_types=1);

return [
    'enabled' => true,

    'service' => 'default', // Default service name for circuit breaker

    // Services registration for circuit breaker
    'services' => [
        'default' => [
            'timeout' => 5000, // Timeout in milliseconds
            'failure_threshold' => 5, // Number of failures before circuit opens
            'reset_timeout' => 30000, // Time in milliseconds before circuit resets
        ], // Default service implementation for circuit breaker

//        'example' => [
//            'enabled' => true,
//            'timeout' => 5000, // Timeout in milliseconds
//            'failure_threshold' => 5, // Number of failures before circuit opens
//            'reset_timeout' => 30000, // Time in milliseconds before circuit resets
//            'service' => '\Haaragard\CircuitBreaker\Contract\CircuitBreakerInterface-class-implementation-with', // Default service name
//        ], // Default service implementation for circuit breaker
    ],
];
