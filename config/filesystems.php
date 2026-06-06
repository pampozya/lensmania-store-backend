<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'visibility' => 'private',
        ],

        'private' => [
            'driver' => 'local',
            'root' => env('FILE_SYSTEM_PRIVATE_ROOT', storage_path('app/private')), // outside web root
            'visibility' => 'private',
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
