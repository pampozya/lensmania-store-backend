<?php

return [
    'default' => env('QUEUE_CONNECTION', 'database'),

    'connections' => [
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CONNECTION', 'mysql'),
            'table' => 'jobs',
            'queue' => env('QUEUE_NAME', 'default'),
            'retry_after' => 90,
            'after_commit' => false,
        ],

        'sync' => [
            'driver' => 'sync',
        ],
    ],

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],
];
