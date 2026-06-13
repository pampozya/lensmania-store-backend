<?php

return [
    'products' => [
        'cinecut' => [
            'name' => 'CineCut',
            'version' => '0.2.0',
            'url' => env('CINECUT_DOWNLOAD_URL', 'https://labs.lensmania.ae/dashboard'),
            'variants' => [
                'mac-arm64' => [
                    'premiere' => env('CINECUT_DOWNLOAD_URL', 'https://labs.lensmania.ae/dashboard'),
                ],
            ],
        ],
    ],
];
