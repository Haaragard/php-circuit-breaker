<?php

declare(strict_types=1);

return [
    'enabled' => true,

    'service' => 'default',

    'services' => [
        'default' => [
            'timeout' => 10_000,
            'failure_threshold' => 5,
            'reset_timeout' => 30_000,
            'service' => \Haaragard\CircuitBreaker\Adapter\LocalStorageAdapter::class,
        ],
        'cache' => [
            'timeout' => 10_000,
            'failure_threshold' => 5,
            'reset_timeout' => 30_000,
            'key_prefix' => 'circuit-breaker:',
            'cache_connection' => 'default',
            'service' => \Haaragard\CircuitBreaker\Adapter\CacheStorageAdapter::class,
        ],
    ],
];
