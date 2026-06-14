<?php

return [
    'products' => [
        'cinecut' => [
            'name' => 'CineCut',
            'version' => '0.2.0',
            'url' => env('CINECUT_DOWNLOAD_URL', 'https://drive.google.com/drive/folders/1DBk727a89Z6NI_4O9994t3m0FM9yefYN?usp=sharing'),
            'variants' => [
                'mac-arm64' => [
                    'premiere' => env('CINECUT_DOWNLOAD_URL', 'https://drive.google.com/drive/folders/1DBk727a89Z6NI_4O9994t3m0FM9yefYN?usp=sharing'),
                ],
            ],
        ],
    ],
];
